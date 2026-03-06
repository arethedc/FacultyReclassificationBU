<?php

namespace App\Http\Controllers;

use App\Models\ReclassificationEvidence;
use Illuminate\Http\Request;

class ReclassificationEvidenceReviewController extends Controller
{
    public function accept(Request $request, ReclassificationEvidence $evidence)
    {
        $this->authorize('review', $evidence);

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $evidence->update([
            'status' => 'accepted',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Evidence accepted.',
            'evidence' => $evidence->fresh(),
        ]);
    }

    public function reject(Request $request, ReclassificationEvidence $evidence)
    {
        $this->authorize('review', $evidence);

        // Require a reason when rejecting (strong rule)
        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:5000'],
        ]);

        $evidence->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => $request->user()->id,
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        return response()->json([
            'message' => 'Evidence rejected.',
            'evidence' => $evidence->fresh(),
        ]);
    }
}
