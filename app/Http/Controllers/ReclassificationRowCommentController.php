<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationApplication;
use App\Models\ReclassificationPeriod;
use App\Models\ReclassificationRowComment;
use App\Models\ReclassificationSectionEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ReclassificationRowCommentController extends Controller
{
    private function assertCommentOwnedByCurrentReviewerRole(Request $request, ReclassificationRowComment $comment): void
    {
        $actorRole = strtolower((string) ($request->user()->role ?? ''));
        $authorRole = strtolower((string) ($comment->author?->role ?? ''));
        if ($authorRole === '') {
            $authorRole = strtolower((string) ($comment->user?->role ?? ''));
        }

        abort_unless($actorRole !== '' && $authorRole === $actorRole, 403);
    }

    private function activePeriod(): ?ReclassificationPeriod
    {
        if (!Schema::hasTable('reclassification_periods')) {
            return null;
        }

        $query = ReclassificationPeriod::query();
        if (Schema::hasColumn('reclassification_periods', 'status')) {
            $query->where('status', 'active');
        } else {
            $query->where('is_open', true);
        }

        return $query->orderByDesc('created_at')->first();
    }

    private function expectedReviewerRoleForStatus(string $status): ?string
    {
        return match ($status) {
            'dean_review' => 'dean',
            'hr_review' => 'hr',
            'vpaa_review' => 'vpaa',
            'president_review' => 'president',
            default => null,
        };
    }

    private function isInActivePeriodScope(ReclassificationApplication $application): bool
    {
        $activePeriod = $this->activePeriod();
        if (!$activePeriod) {
            return false;
        }

        $hasPeriodId = Schema::hasColumn('reclassification_applications', 'period_id');
        if (!$hasPeriodId) {
            return !empty($activePeriod->cycle_year)
                && (string) ($application->cycle_year ?? '') === (string) $activePeriod->cycle_year;
        }

        if ((int) ($application->period_id ?? 0) === (int) $activePeriod->id) {
            return true;
        }

        return empty($application->period_id)
            && !empty($activePeriod->cycle_year)
            && (string) ($application->cycle_year ?? '') === (string) $activePeriod->cycle_year;
    }

    private function assertReviewerOwnsCurrentStage(Request $request, ReclassificationApplication $application): void
    {
        $role = strtolower((string) ($request->user()->role ?? ''));
        $status = (string) ($application->status ?? '');
        $expectedRole = $this->expectedReviewerRoleForStatus($status);

        abort_unless($expectedRole && $role === $expectedRole, 403);

        if ($role === 'dean') {
            $application->loadMissing('faculty');
            $userDepartmentId = $request->user()->department_id;
            abort_unless($userDepartmentId && $application->faculty?->department_id === $userDepartmentId, 403);
        }

        abort_unless($this->isInActivePeriodScope($application), 403);
    }

    private function isEntryRemoved(?ReclassificationSectionEntry $entry): bool
    {
        if (!$entry) {
            return false;
        }

        $data = is_array($entry->data) ? $entry->data : [];
        $value = $data['is_removed'] ?? false;

        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function respond(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function store(Request $request, ReclassificationApplication $application, ReclassificationSectionEntry $entry)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);
        $this->assertReviewerOwnsCurrentStage($request, $application);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'visibility' => ['required', 'in:faculty_visible,internal'],
            'action_type' => ['nullable', 'required_if:visibility,faculty_visible', 'in:requires_action,info'],
        ]);

        $entry->loadMissing('section');
        abort_unless($entry->section && $entry->section->reclassification_application_id === $application->id, 404);
        abort_unless(!$this->isEntryRemoved($entry), 422, 'This entry was removed by faculty.');

        $body = trim((string) $validated['body']);
        $visibility = (string) ($validated['visibility'] ?? 'faculty_visible');
        $hasActionTypeColumn = Schema::hasColumn('reclassification_row_comments', 'action_type');
        $actionType = $visibility === 'internal'
            ? 'info'
            : (string) ($validated['action_type'] ?? 'requires_action');

        $duplicateQuery = ReclassificationRowComment::query()
            ->where('reclassification_application_id', $application->id)
            ->where('reclassification_section_entry_id', $entry->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where('body', $body)
            ->where('visibility', $visibility)
            ->where('created_at', '>=', now()->subSeconds(15));
        if ($hasActionTypeColumn) {
            $duplicateQuery->where('action_type', $actionType);
        }
        $recentDuplicate = $duplicateQuery->exists();
        if ($recentDuplicate) {
            return $this->respond($request, 'Comment already saved.');
        }

        $payload = [
            'reclassification_application_id' => $application->id,
            'reclassification_section_entry_id' => $entry->id,
            'user_id' => $request->user()->id,
            'body' => $body,
            'visibility' => $visibility,
            'parent_id' => null,
            'status' => 'open',
        ];
        if ($hasActionTypeColumn) {
            $payload['action_type'] = $actionType;
        }

        ReclassificationRowComment::create($payload);

        return $this->respond($request, 'Comment added.');
    }

    public function reply(Request $request, ReclassificationRowComment $comment)
    {
        $application = $comment->application()->firstOrFail();
        $comment->loadMissing('entry');

        abort_unless($request->user()->id === $application->faculty_user_id, 403);
        abort_unless($application->status === 'returned_to_faculty', 422);
        abort_unless($comment->parent_id === null, 422);
        abort_unless($comment->visibility === 'faculty_visible', 422);
        abort_unless(
            !Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($comment->action_type ?? 'requires_action') === 'requires_action',
            422
        );
        abort_unless($comment->status !== 'resolved', 422);
        abort_unless(
            !$this->isEntryRemoved($comment->entry),
            422,
            'Cannot reply to a comment tied to a removed entry.'
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $body = trim((string) $validated['body']);

        $recentDuplicate = ReclassificationRowComment::query()
            ->where('reclassification_application_id', $comment->reclassification_application_id)
            ->where('reclassification_section_entry_id', $comment->reclassification_section_entry_id)
            ->where('user_id', $request->user()->id)
            ->where('parent_id', $comment->id)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->exists();
        if ($recentDuplicate) {
            return $this->respond($request, 'Reply already saved.');
        }

        $replyPayload = [
            'reclassification_application_id' => $comment->reclassification_application_id,
            'reclassification_section_entry_id' => $comment->reclassification_section_entry_id,
            'user_id' => $request->user()->id,
            'body' => $body,
            'visibility' => 'faculty_visible',
            'parent_id' => $comment->id,
            'status' => 'open',
        ];
        if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            $replyPayload['action_type'] = 'requires_action';
        }

        ReclassificationRowComment::create($replyPayload);

        $comment->update([
            'status' => 'addressed',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respond($request, 'Reply sent. Comment marked as addressed.');
    }

    public function updateReply(Request $request, ReclassificationRowComment $comment)
    {
        $application = $comment->application()->firstOrFail();
        $parent = $comment->parent()->firstOrFail();
        $comment->loadMissing('entry');

        abort_unless($request->user()->id === $application->faculty_user_id, 403);
        abort_unless($application->status === 'returned_to_faculty', 422);
        abort_unless(!is_null($comment->parent_id), 422);
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        abort_unless($parent->visibility === 'faculty_visible', 422);
        abort_unless(
            !Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($parent->action_type ?? 'requires_action') === 'requires_action',
            422
        );
        abort_unless(($parent->status ?? 'open') === 'addressed', 422);
        abort_unless(
            !$this->isEntryRemoved($comment->entry),
            422,
            'Cannot edit a reply tied to a removed entry.'
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $body = trim((string) $validated['body']);
        if ($body === trim((string) ($comment->body ?? ''))) {
            return $this->respond($request, 'No changes to save.');
        }

        $comment->update([
            'body' => $body,
        ]);

        return $this->respond($request, 'Reply updated.');
    }

    public function address(Request $request, ReclassificationRowComment $comment)
    {
        $application = $comment->application()->firstOrFail();
        $comment->loadMissing('entry');

        abort_unless($request->user()->id === $application->faculty_user_id, 403);
        abort_unless($application->status === 'returned_to_faculty', 422);
        abort_unless($comment->parent_id === null, 422);
        abort_unless($comment->visibility === 'faculty_visible', 422);
        abort_unless(
            !Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($comment->action_type ?? 'requires_action') === 'requires_action',
            422
        );
        abort_unless(
            !$this->isEntryRemoved($comment->entry),
            422,
            'Cannot mark addressed for a comment tied to a removed entry.'
        );
        if ($comment->status === 'addressed') {
            return $this->respond($request, 'Comment already addressed.');
        }
        abort_unless($comment->status !== 'resolved', 422);

        $comment->update([
            'status' => 'addressed',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respond($request, 'Comment marked as addressed.');
    }

    public function resolve(Request $request, ReclassificationRowComment $comment)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $comment->loadMissing(['author', 'user']);
        $application = $comment->application()->with('faculty')->firstOrFail();
        $this->assertReviewerOwnsCurrentStage($request, $application);
        $this->assertCommentOwnedByCurrentReviewerRole($request, $comment);
        abort_unless($comment->parent_id === null, 422);
        abort_unless(
            !Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($comment->action_type ?? 'requires_action') === 'requires_action',
            422,
            'No-action comments do not need resolution.'
        );
        if ($comment->status === 'resolved') {
            return $this->respond($request, 'Comment already resolved.');
        }
        abort_unless(
            $comment->status === 'addressed',
            422,
            'Cannot resolve yet. Faculty must mark this comment as addressed first.'
        );
        $comment->update([
            'status' => 'resolved',
            'resolved_by_user_id' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        return $this->respond($request, 'Comment marked as resolved.');
    }

    public function reopen(Request $request, ReclassificationRowComment $comment)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $comment->loadMissing(['author', 'user']);
        $application = $comment->application()->with('faculty')->firstOrFail();
        $this->assertReviewerOwnsCurrentStage($request, $application);
        $this->assertCommentOwnedByCurrentReviewerRole($request, $comment);
        $comment->loadMissing('entry');

        abort_unless($comment->parent_id === null, 422);
        abort_unless($comment->visibility === 'faculty_visible', 422);
        abort_unless(
            !Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($comment->action_type ?? 'requires_action') === 'requires_action',
            422,
            'Only action-required comments can be reopened.'
        );
        abort_unless((string) ($comment->status ?? 'open') === 'addressed', 422, 'Only addressed comments can be reopened.');
        abort_unless(
            !$this->isEntryRemoved($comment->entry),
            422,
            'Cannot reopen a comment tied to a removed entry.'
        );

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $body = trim((string) $validated['body']);

        $recentDuplicate = ReclassificationRowComment::query()
            ->where('reclassification_application_id', $comment->reclassification_application_id)
            ->where('reclassification_section_entry_id', $comment->reclassification_section_entry_id)
            ->where('user_id', $request->user()->id)
            ->where('parent_id', $comment->id)
            ->where('body', $body)
            ->where('created_at', '>=', now()->subSeconds(15))
            ->exists();
        if ($recentDuplicate) {
            return $this->respond($request, 'Follow-up concern already saved.');
        }

        $payload = [
            'reclassification_application_id' => $comment->reclassification_application_id,
            'reclassification_section_entry_id' => $comment->reclassification_section_entry_id,
            'user_id' => $request->user()->id,
            'body' => $body,
            'visibility' => 'faculty_visible',
            'parent_id' => $comment->id,
            'status' => 'open',
        ];
        if (Schema::hasColumn('reclassification_row_comments', 'action_type')) {
            $payload['action_type'] = 'requires_action';
        }

        ReclassificationRowComment::create($payload);

        $comment->update([
            'status' => 'open',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respond($request, 'Follow-up concern added. Comment reopened as action required.');
    }

    public function undoResolve(Request $request, ReclassificationRowComment $comment)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $comment->loadMissing(['author', 'user']);
        $application = $comment->application()->with('faculty')->firstOrFail();
        $this->assertReviewerOwnsCurrentStage($request, $application);
        $this->assertCommentOwnedByCurrentReviewerRole($request, $comment);
        abort_unless($comment->parent_id === null, 422);
        abort_unless(
            !Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($comment->action_type ?? 'requires_action') === 'requires_action',
            422,
            'Only action-required comments can be changed.'
        );
        abort_unless((string) ($comment->status ?? 'open') === 'resolved', 422, 'Only resolved comments can be undone.');

        $comment->update([
            'status' => 'addressed',
            'resolved_by_user_id' => null,
            'resolved_at' => null,
        ]);

        return $this->respond($request, 'Resolved status undone. Comment is addressed again.');
    }

    public function destroy(Request $request, ReclassificationRowComment $comment)
    {
        abort_unless(in_array($request->user()->role, ['dean', 'hr', 'vpaa', 'president'], true), 403);

        $comment->loadMissing(['author', 'user']);
        $application = $comment->application()->with('faculty')->firstOrFail();
        $this->assertReviewerOwnsCurrentStage($request, $application);
        $this->assertCommentOwnedByCurrentReviewerRole($request, $comment);
        $parent = null;
        if (!is_null($comment->parent_id)) {
            $parent = ReclassificationRowComment::query()->find($comment->parent_id);
        }

        abort_unless(($comment->status ?? 'open') !== 'resolved', 422, 'Resolved comments cannot be removed.');

        ReclassificationRowComment::where('parent_id', $comment->id)->delete();
        $comment->delete();

        // If a follow-up child was removed, recompute parent state so reviewer
        // actions (Mark Resolved / Reopen) become available again when applicable.
        if ($parent
            && $parent->visibility === 'faculty_visible'
            && (!Schema::hasColumn('reclassification_row_comments', 'action_type')
                || ($parent->action_type ?? 'requires_action') === 'requires_action')
            && ($parent->status ?? 'open') !== 'resolved'
        ) {
            $latestChild = ReclassificationRowComment::query()
                ->where('parent_id', $parent->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['user_id', 'created_at', 'id'])
                ->last();

            $nextStatus = 'addressed';
            if ($latestChild && (int) ($latestChild->user_id ?? 0) !== (int) ($application->faculty_user_id ?? 0)) {
                $nextStatus = 'open';
            }

            $parent->update([
                'status' => $nextStatus,
                'resolved_by_user_id' => null,
                'resolved_at' => null,
            ]);
        }

        return $this->respond($request, 'Comment removed.');
    }
}
