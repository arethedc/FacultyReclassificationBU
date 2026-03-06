@php
    $canFacultyRespond = (($application->status ?? '') === 'returned_to_faculty');
    $currentUserId = (int) (auth()->id() ?? 0);
@endphp

<div x-data="{
        showResolvedInMain: true,
        rootComments() {
            return (row.comments || [])
                .filter(item => !item.parent_id && (item.action_type || 'requires_action') !== 'info');
        },
        commentTimestamp(item) {
            const raw = Date.parse(String(item?.created_at || ''));
            return Number.isNaN(raw) ? 0 : raw;
        },
        recentComments() {
            return this.rootComments()
                .filter(item => (item.status || 'open') !== 'resolved')
                .slice()
                .sort((a, b) => this.commentTimestamp(b) - this.commentTimestamp(a));
        },
        resolvedComments() {
            return this.rootComments()
                .filter(item => (item.status || 'open') === 'resolved')
                .slice()
                .sort((a, b) => this.commentTimestamp(b) - this.commentTimestamp(a));
        },
        stageSortIndex(role) {
            const normalized = String(role || '').toLowerCase();
            if (normalized === 'dean') return 10;
            if (normalized === 'hr') return 20;
            if (normalized === 'vpaa') return 30;
            if (normalized === 'president') return 40;
            return 90;
        },
        roleStageLabel(role) {
            const normalized = String(role || '').toLowerCase();
            if (normalized === 'dean') return 'Dean Stage';
            if (normalized === 'hr') return 'HR Stage';
            if (normalized === 'vpaa') return 'VPAA Stage';
            if (normalized === 'president') return 'President Stage';
            return 'Reviewer Stage';
        },
        ordinalReturnLabel(index) {
            const labels = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh', 'Eighth', 'Ninth', 'Tenth'];
            if (index > 0 && index <= labels.length) return `${labels[index - 1]} Return`;
            return `${index}th Return`;
        },
        snapshotTimestamp(snapshot) {
            const fromLabel = Date.parse(String(snapshot?.dateLabel || ''));
            if (!Number.isNaN(fromLabel)) return fromLabel;
            const first = Array.isArray(snapshot?.comments) ? snapshot.comments[0] : null;
            return this.commentTimestamp(first);
        },
        resolvedRoleSnapshots() {
            const roleGroups = new Map();
            this.resolvedComments().forEach((comment) => {
                const role = String(comment?.author_role || '').toLowerCase() || 'reviewer';
                if (!roleGroups.has(role)) {
                    roleGroups.set(role, []);
                }
                roleGroups.get(role).push(comment);
            });

            return Array.from(roleGroups.entries())
                .map(([role, comments]) => {
                    const snapshotMap = new Map();
                    comments.forEach((comment) => {
                        const key = String(comment?.return_date_label || comment?.return_label || 'current_review');
                        if (!snapshotMap.has(key)) {
                            snapshotMap.set(key, {
                                key,
                                dateLabel: String(comment?.return_date_label || ''),
                                fallbackLabel: String(comment?.return_label || ''),
                                comments: [],
                            });
                        }
                        snapshotMap.get(key).comments.push(comment);
                    });

                    const snapshots = Array.from(snapshotMap.values())
                        .sort((a, b) => this.snapshotTimestamp(a) - this.snapshotTimestamp(b))
                        .map((snapshot, index) => {
                            const snapshotLabel = snapshot.dateLabel
                                ? this.ordinalReturnLabel(index + 1)
                                : (snapshot.fallbackLabel || this.ordinalReturnLabel(index + 1));
                            return {
                                ...snapshot,
                                label: snapshotLabel,
                                comments: snapshot.comments
                                    .slice()
                                    .sort((a, b) => this.commentTimestamp(b) - this.commentTimestamp(a)),
                            };
                        });

                    return {
                        role,
                        stageLabel: this.roleStageLabel(role),
                        snapshots,
                    };
                })
                .sort((a, b) => this.stageSortIndex(a.role) - this.stageSortIndex(b.role));
        },
        threadReplies(commentId) {
            return (row.comments || [])
                .filter(item => Number(item.parent_id || 0) === Number(commentId))
                .slice()
                .sort((a, b) => {
                    const aTime = Date.parse(String(a?.created_at || '')) || 0;
                    const bTime = Date.parse(String(b?.created_at || '')) || 0;
                    if (aTime !== bTime) return aTime - bTime;
                    return (Number(a?.id || 0) - Number(b?.id || 0));
                });
        },
        isReplyEditable(comment, reply) {
            if (Number(reply?.author_id || 0) !== {{ $currentUserId }}) return false;
            if (!String(reply?.update_reply_url || '').trim()) return false;

            const replies = this.threadReplies(comment?.id);
            const facultyReplies = replies.filter(item => Number(item?.author_id || 0) === {{ $currentUserId }});
            if (!facultyReplies.length) return false;

            const latestFacultyReply = facultyReplies[facultyReplies.length - 1];
            const latestFacultyIndex = replies.findIndex(item => Number(item?.id || 0) === Number(latestFacultyReply?.id || 0));
            const latestReviewerIndex = (() => {
                for (let i = replies.length - 1; i >= 0; i -= 1) {
                    if (Number(replies[i]?.author_id || 0) !== {{ $currentUserId }}) {
                        return i;
                    }
                }
                return -1;
            })();

            if (latestReviewerIndex >= 0 && latestFacultyIndex <= latestReviewerIndex) return false;

            return Number(latestFacultyReply?.id || 0) === Number(reply?.id || 0);
        }
    }"
     @faculty-comments-toggle-resolved.window="showResolvedInMain = true"
     data-skip-row-validation
     data-return-lock-ignore
     class="w-full rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm">
    <div class="text-sm font-semibold text-gray-800">Reviewer's Comments</div>

    <div class="mt-3 space-y-3">
        <template x-for="comment in recentComments()" :key="`recent-${comment.id}`">
            <div class="w-full rounded-lg border border-gray-200 bg-white p-2.5 text-left space-y-1">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold text-gray-800" x-text="`${comment.author || 'Reviewer'} - ${comment.created_at_label || comment.created_at || ''}`"></div>
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span x-show="(comment.action_type || 'requires_action') === 'requires_action' && (comment.status || 'open') === 'open'"
                              class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[10px] font-medium text-amber-700">Action required</span>
                        <span x-show="(comment.status || 'open') === 'addressed'"
                              class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[10px] font-medium text-blue-700">Addressed by faculty</span>
                    </div>
                </div>

                <div class="text-[10px] font-semibold text-gray-500">Reviewer Comment:</div>
                <div class="break-words text-[13px] leading-5 text-gray-800" x-text="comment.body"></div>

                <template x-if="threadReplies(comment.id).length">
                    <div class="mt-1 rounded-md border-t border-gray-200 pt-1 space-y-1.5">
                        <template x-for="(reply, replyIndex) in threadReplies(comment.id)" :key="reply.id">
                            <div class="text-[11px] text-gray-700"
                                 :class="replyIndex > 0 ? 'border-t border-gray-200 pt-1.5' : ''"
                                 x-data="{ editing: false, editBody: String(reply.body || ''), saving: false }">
                                <div class="text-[11px] font-semibold text-gray-800" x-text="`${reply.author || 'Faculty'} - ${reply.created_at_label || reply.created_at || ''}`"></div>
                                <div class="mt-0.5 flex items-start justify-between gap-2">
                                    <div class="text-[10px] font-semibold text-gray-500"
                                         x-text="Number(reply.author_id || 0) === {{ $currentUserId }} ? 'Faculty Reply:' : 'Reviewer Comment:'"></div>
                                    @if($canFacultyRespond)
                                        <button type="button"
                                                x-show="!editing && isReplyEditable(comment, reply)"
                                                @click="editBody = String(reply.body || ''); editing = true;"
                                                class="px-2 py-0.5 rounded-lg border border-blue-200 bg-blue-50 text-[10px] font-semibold text-blue-700 hover:bg-blue-100 transition">
                                            Edit
                                        </button>
                                    @endif
                                </div>
                                <div class="mt-0.5 break-words text-[13px] leading-5 text-gray-800" x-show="!editing" x-text="reply.body"></div>
                                <div class="mt-1 space-y-2" x-show="editing">
                                    <textarea rows="2"
                                              x-model="editBody"
                                              x-bind:disabled="saving"
                                              class="w-full rounded-lg border-gray-300 text-xs"
                                              placeholder="Edit your reply..."></textarea>
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                @click="editBody = String(reply.body || ''); editing = false;"
                                                x-bind:disabled="saving"
                                                class="px-2 py-0.5 rounded-lg border border-gray-300 bg-white text-[10px] font-semibold text-gray-700 hover:bg-gray-50">
                                            Cancel
                                        </button>
                                        <button type="button"
                                                @click="
                                                    const body = String(editBody || '').trim();
                                                    if (!body || saving || body === String(reply.body || '').trim() || !String(reply.update_reply_url || '').trim()) return;
                                                    saving = true;
                                                    window.BuFacultyInlineComments.updateReply(reply, body)
                                                        .then(() => { reply.body = body; editing = false; })
                                                        .finally(() => { saving = false; });
                                                "
                                                x-bind:disabled="saving || !String(editBody || '').trim() || String(editBody || '').trim() === String(reply.body || '').trim() || !String(reply.update_reply_url || '').trim()"
                                                x-bind:class="(saving || !String(editBody || '').trim() || String(editBody || '').trim() === String(reply.body || '').trim() || !String(reply.update_reply_url || '').trim()) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-bu text-white hover:bg-bu-dark cursor-pointer'"
                                                class="px-2 py-0.5 rounded-lg text-[10px] font-semibold transition">
                                            <span x-text="saving ? 'Saving...' : 'Save changes'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                @if($canFacultyRespond)
                    <div class="mt-3 space-y-2"
                         x-data="{ replyBody: '', replying: false }"
                         x-show="(comment.action_type || 'requires_action') === 'requires_action' && (comment.status || 'open') === 'open'">
                        <textarea rows="2"
                                  x-model="replyBody"
                                  x-bind:disabled="replying"
                                  class="w-full rounded-lg border-gray-300 text-sm"
                                  placeholder="Reply to this comment..."></textarea>
                        <div class="flex items-center justify-end gap-2">
                            <button type="button"
                                    @click="
                                        const body = String(replyBody || '').trim();
                                        if (!body || replying || !String(comment.reply_url || '').trim()) return;
                                        replying = true;
                                        window.BuFacultyInlineComments.reply(comment, body)
                                            .then(() => {
                                                if (Array.isArray(row.comments)) {
                                                    row.comments.push({
                                                        id: `local-reply-${comment.id}-${Date.now()}`,
                                                        parent_id: comment.id,
                                                        body,
                                                        author: 'You',
                                                        author_id: {{ $currentUserId }},
                                                        created_at: new Date().toISOString(),
                                                        created_at_label: 'Just now',
                                                    });
                                                }
                                                replyBody = '';
                                                comment.status = 'addressed';
                                            })
                                            .finally(() => {
                                                replying = false;
                                            });
                                    "
                                    x-bind:disabled="!String(replyBody || '').trim() || !String(comment.reply_url || '').trim() || replying"
                                    x-bind:class="(!String(replyBody || '').trim() || !String(comment.reply_url || '').trim() || replying) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-bu text-white hover:bg-bu-dark cursor-pointer'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                <span x-text="replying ? 'Saving...' : 'Reply and Mark Addressed'"></span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </template>

        <template x-if="resolvedComments().length > 0">
            <div class="space-y-2">
    <div class="text-sm font-semibold text-gray-800">Resolved Comments</div>
                <template x-for="stage in resolvedRoleSnapshots()" :key="`stage-${stage.role}`">
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-2.5 space-y-2">
                        <div class="text-xs font-semibold text-slate-800" x-text="stage.stageLabel"></div>

                        <template x-for="snapshot in stage.snapshots" :key="`snapshot-${stage.role}-${snapshot.key}`">
                            <div class="rounded-md border border-slate-200 bg-white px-2.5 py-2 space-y-2">
                                <div class="text-[11px] font-semibold text-slate-700"
                                     x-text="snapshot.label + (snapshot.dateLabel ? ` - ${snapshot.dateLabel}` : '')"></div>

                                <template x-for="comment in snapshot.comments" :key="`resolved-${comment.id}`">
                                    <div class="w-full rounded-lg border border-gray-200 bg-white p-2.5 text-left space-y-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <div class="text-[11px] font-semibold text-gray-800" x-text="`${comment.author || 'Reviewer'} - ${comment.created_at_label || comment.created_at || ''}`"></div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex items-center rounded-full border border-green-200 bg-green-50 px-2 py-0.5 text-[10px] font-medium text-green-700">Resolved by reviewer</span>
                                            </div>
                                        </div>

                                        <div class="text-[10px] font-semibold text-gray-500">Reviewer's Comment:</div>
                                        <div class="break-words text-[13px] leading-5 text-gray-800" x-text="comment.body"></div>

                                        <template x-if="threadReplies(comment.id).length">
                                            <div class="mt-1 rounded-md border-t border-gray-200 pt-1 space-y-1.5">
                                                <template x-for="(reply, replyIndex) in threadReplies(comment.id)" :key="reply.id">
                                                    <div class="text-[11px] text-gray-700"
                                                         :class="replyIndex > 0 ? 'border-t border-gray-200 pt-1.5' : ''"
                                                         x-data="{ editing: false, editBody: String(reply.body || ''), saving: false }">
                                                        <div class="text-[11px] font-semibold text-gray-800" x-text="`${reply.author || 'Faculty'} - ${reply.created_at_label || reply.created_at || ''}`"></div>
                                                        <div class="mt-0.5 flex items-start justify-between gap-2">
                                                            <div class="text-[10px] font-semibold text-gray-500"
                                                                 x-text="Number(reply.author_id || 0) === {{ $currentUserId }} ? 'Faculty Reply:' : 'Reviewer Comment:'"></div>
                                                        </div>
                                                        <div class="mt-0.5 break-words text-[13px] leading-5 text-gray-800" x-show="!editing" x-text="reply.body"></div>
                                                        <div class="mt-1 space-y-2" x-show="editing">
                                                            <textarea rows="2"
                                                                      x-model="editBody"
                                                                      x-bind:disabled="saving"
                                                                      class="w-full rounded-lg border-gray-300 text-xs"
                                                                      placeholder="Edit your reply..."></textarea>
                                                            <div class="flex items-center justify-end gap-1.5">
                                                                <button type="button"
                                                                        @click="editBody = String(reply.body || ''); editing = false;"
                                                                        x-bind:disabled="saving"
                                                                        class="px-2 py-0.5 rounded-lg border border-gray-300 bg-white text-[10px] font-semibold text-gray-700 hover:bg-gray-50">
                                                                    Cancel
                                                                </button>
                                                                <button type="button"
                                                                        @click="
                                                                            const body = String(editBody || '').trim();
                                                                            if (!body || saving || body === String(reply.body || '').trim() || !String(reply.update_reply_url || '').trim()) return;
                                                                            saving = true;
                                                                            window.BuFacultyInlineComments.updateReply(reply, body)
                                                                                .then(() => { reply.body = body; editing = false; })
                                                                                .finally(() => { saving = false; });
                                                                        "
                                                                        x-bind:disabled="saving || !String(editBody || '').trim() || String(editBody || '').trim() === String(reply.body || '').trim() || !String(reply.update_reply_url || '').trim()"
                                                                        x-bind:class="(saving || !String(editBody || '').trim() || String(editBody || '').trim() === String(reply.body || '').trim() || !String(reply.update_reply_url || '').trim()) ? 'bg-gray-200 text-gray-500 cursor-not-allowed' : 'bg-bu text-white hover:bg-bu-dark cursor-pointer'"
                                                                        class="px-2 py-0.5 rounded-lg text-[10px] font-semibold transition">
                                                                    <span x-text="saving ? 'Saving...' : 'Save changes'"></span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </template>
    </div>
</div>
