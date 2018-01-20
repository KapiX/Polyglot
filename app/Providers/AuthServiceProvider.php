<?php

namespace Polyglot\Providers;

use Polyglot\File;
use Polyglot\Project;
use Polyglot\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'Polyglot\Model' => 'Polyglot\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('global-settings', function($user) {
            return $user->role === 2; // global admin
        });
        Gate::define('add-project', function($user) {
            return $user->role === 1 || $user->role === 2; // developer
        });
        Gate::define('modify-project', function($user, Project $project) {
            $u = $project->users()->where('users.id', $user->id);
            if($u->count() > 0) {
                return $u->first()->pivot->role === 2;
            }
            return false;
        });
        Gate::define('modify-file', function($user, File $file) {
            $u = $file->project->users()->where('users.id', $user->id);
            if($u->count() > 0) {
                return $u->first()->pivot->role === 2;
            }
            return false;
        });
    }
}
