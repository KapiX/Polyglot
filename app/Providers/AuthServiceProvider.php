<?php

namespace App\Providers;

use App\Models\File;
use App\Models\Language;
use App\Models\Project;
use App\Models\Text;
use App\Models\User;
use App\Models\GlossaryEntry;
use App\Policies\GlossaryEntryPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        
        Gate::define('global-settings', function(User $user) {
            return $user->isAdministrator(); // global admin
        });
    }
}
