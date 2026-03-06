@php
    $currentReviewerRole = strtolower((string) ($reviewerRole ?? ''));
    $commentAuthorRole = strtolower((string) ($comment->author?->role ?? ''));
    $canManageThisThread = $currentReviewerRole !== '' && $commentAuthorRole === $currentReviewerRole;
    $visibilityClass = $comment->visibility === 'faculty_visible'
        ? 'bg-green-50 text-green-700 border-green-200'
        : 'bg-gray-50 text-gray-600 border-gray-200';
    $visibilityLabel = $comment->visibility === 'faculty_visible'
        ? 'Visible to faculty'
        : 'Internal';
    $commentActionType = (string) ($comment->action_type ?? 'requires_action');
    if ($commentActionType === 'info') {
        $commentActionClass = 'bg-slate-50 text-slate-700 border-slate-200';
        $commentActionLabel = 'No action required';
    } else {
        $commentActionClass = match((string) ($comment->status ?? 'open')) {
            'resolved' => 'bg-green-50 text-green-700 border-green-200',
            'addressed' => 'bg-blue-50 text-blue-700 border-blue-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
        $commentActionLabel = match((string) ($comment->status ?? 'open')) {
            'resolved' => 'Resolved by reviewer',
            'addressed' => 'Addressed by faculty',
            default => 'Action required',
        };
    }
    $status = $comment->status ?? 'open';
    $replies = $rowComments
        ->where('parent_id', $comment->id)
        ->sortBy('created_at')
        ->values();
@endphp
<div class="rounded-lg border border-gray-200 bg-white p-2.5 text-left space-y-1">
    <div class="flex items-center justify-between gap-2">
        <div class="text-[11px] font-semibold text-gray-800">
            {{ $comment->author?->name ?? 'Reviewer' }} - {{ optional($comment->created_at)->format('M d, Y g:i A') }}
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $visibilityClass }}">
                {{ $visibilityLabel }}
            </span>
            @if($comment->visibility === 'faculty_visible')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border {{ $commentActionClass }}">
                    {{ $commentActionLabel }}
                </span>
            @endif
            @if($canManageThisThread && ($comment->status ?? 'open') !== 'resolved')
                <form method="POST"
                      action="{{ route('reclassification.row-comments.destroy', $comment) }}"
                      data-async-action
                      data-async-refresh-target="#reviewer-content"
                      data-loading-text="Removing..."
                      data-loading-message="Removing comment..."
                      data-confirm="Remove this comment thread?">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-2 py-0.5 rounded border border-red-200 bg-red-50 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                        Remove
                    </button>
                </form>
            @endif
        </div>
    </div>
    <div class="text-[10px] font-semibold text-gray-500">Reviewer Comment:</div>
    <div class="text-[13px] leading-5 text-gray-800 break-words">{{ $comment->body }}</div>

    @if($replies->isNotEmpty())
        <div class="mt-1 rounded-md border-t border-gray-200 pt-1 space-y-1.5">
            @foreach($replies as $reply)
                @php
                    $replyVisibility = (string) ($reply->visibility ?? 'faculty_visible');
                    $replyActionType = (string) ($reply->action_type ?? 'requires_action');
                    $isFacultyReply = (int) ($reply->user_id ?? 0) === (int) ($application->faculty_user_id ?? 0);
                    $isFollowUpConcern = $replyVisibility === 'faculty_visible' && $replyActionType === 'requires_action' && !$isFacultyReply;
                    $replyLabel = $isFacultyReply ? 'Faculty Reply' : 'Reviewer Comment';
                @endphp
                <div class="text-[11px] text-gray-700 @if(!$loop->first) border-t border-gray-200 pt-1.5 @endif">
                    <div class="flex items-start justify-between gap-2">
                        <div class="text-[11px] font-semibold text-gray-800">
                            {{ $reply->author?->name ?? 'Faculty' }} - {{ optional($reply->created_at)->format('M d, Y g:i A') }}
                        </div>
                        @php
                            $replyAuthorRole = strtolower((string) ($reply->author?->role ?? ''));
                            $canManageFollowUp = $currentReviewerRole !== '' && $replyAuthorRole === $currentReviewerRole;
                        @endphp
                        @if($isFollowUpConcern && $canManageFollowUp)
                            <form method="POST"
                                  action="{{ route('reclassification.row-comments.destroy', $reply) }}"
                                  data-async-action
                                  data-async-refresh-target="#reviewer-content"
                                  data-loading-text="Removing..."
                                  data-loading-message="Removing follow-up concern..."
                                  data-confirm="Remove this follow-up concern?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="px-2 py-0.5 rounded border border-red-200 bg-red-50 text-[10px] font-semibold text-red-700 hover:bg-red-100">
                                    Remove
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="mt-0.5 text-[10px] font-semibold text-gray-500">{{ $replyLabel }}:</div>
                    <div class="mt-0.5 break-words text-[13px] leading-5 text-gray-800">{{ $reply->body }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if(
        $canManageThisThread
        &&
        $comment->visibility === 'faculty_visible'
        && ($comment->action_type ?? 'requires_action') === 'requires_action'
        && in_array((string) ($comment->status ?? 'open'), ['addressed', 'resolved'], true)
    )
        <div class="mt-2 space-y-2"
             x-data="{ reopenOpen: false, reopenBody: '' }">
            <div class="flex justify-end gap-2">
                @if(($comment->status ?? 'open') === 'addressed')
                    <form method="POST"
                          action="{{ route('reclassification.row-comments.resolve', $comment) }}"
                          data-async-action
                          data-async-refresh-target="#reviewer-content"
                          data-loading-text="Saving..."
                          data-loading-message="Resolving comment...">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded-lg border border-green-200 bg-green-50 text-[11px] font-semibold text-green-700 hover:bg-green-100">
                            Mark Resolved
                        </button>
                    </form>
                    <button type="button"
                            @click="reopenOpen = !reopenOpen"
                            class="px-2.5 py-1 rounded-lg border border-amber-200 bg-amber-50 text-[11px] font-semibold text-amber-700 hover:bg-amber-100">
                        Reopen Comment
                    </button>
                @endif
                @if(($comment->status ?? 'open') === 'resolved')
                    <form method="POST"
                          action="{{ route('reclassification.row-comments.undo-resolve', $comment) }}"
                          data-async-action
                          data-async-refresh-target="#reviewer-content"
                          data-loading-text="Saving..."
                          data-loading-message="Undoing resolved status...">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded-lg border border-blue-200 bg-blue-50 text-[11px] font-semibold text-blue-700 hover:bg-blue-100">
                            Undo Resolved
                        </button>
                    </form>
                @endif
            </div>
            @if(($comment->status ?? 'open') === 'addressed')
                <div x-show="reopenOpen"
                     x-cloak
                     class="rounded-lg border border-amber-200 bg-amber-50/40 p-2.5">
                    <form method="POST"
                          action="{{ route('reclassification.row-comments.reopen', $comment) }}"
                          data-async-action
                          data-async-refresh-target="#reviewer-content"
                          data-loading-text="Saving..."
                          data-loading-message="Reopening comment..."
                          class="space-y-2">
                        @csrf
                        <textarea name="body"
                                  rows="2"
                                  required
                                  x-model="reopenBody"
                                  class="w-full rounded-lg border-gray-300 text-xs"
                                  placeholder="Follow-up concern..."></textarea>
                        <div class="flex justify-end gap-2">
                            <button type="button"
                                    @click="reopenOpen = false; reopenBody = ''"
                                    class="px-2.5 py-1 rounded-lg border border-gray-300 bg-white text-[11px] font-semibold text-gray-700 hover:bg-gray-50">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="!String(reopenBody || '').trim()"
                                    :class="!String(reopenBody || '').trim() ? 'opacity-60 cursor-not-allowed' : ''"
                                    class="px-2.5 py-1 rounded-lg border border-amber-200 bg-amber-50 text-[11px] font-semibold text-amber-700 hover:bg-amber-100">
                                Reopen as Required Action
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif
</div>
