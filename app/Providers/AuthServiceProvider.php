<?php

namespace App\Providers;

use App\Models\ReclassificationEvidence;
use App\Policies\ReclassificationEvidencePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        ReclassificationEvidence::class => ReclassificationEvidencePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
