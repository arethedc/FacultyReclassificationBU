<?php

namespace App\Policies;

use App\Models\ReclassificationEvidence;
use App\Models\User;

class ReclassificationEvidencePolicy
{
    public function review(User $user, ReclassificationEvidence $evidence): bool
    {
        $allowedRoles = ['dean', 'hr', 'vpaa', 'president'];
        if (!in_array($user->role, $allowedRoles, true)) {
            return false;
        }

        // Ensure we can reach application status through section -> application
        $section = $evidence->section;
        if (!$section) return false;

        $app = $section->application;
        if (!$app) return false;

        // No review while draft
        if ($app->status === 'draft') return false;

        // Only during review stages
        $reviewStatuses = ['dean_review', 'hr_review', 'vpaa_review', 'president_review'];
        return in_array($app->status, $reviewStatuses, true);
    }
}
