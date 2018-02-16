<?php

namespace Polyglot\Providers;

use Polyglot\File;
use Polyglot\Language;
use Polyglot\Project;
use Polyglot\Text;
use Polyglot\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
            if($user->role === 2) // global admin
                return true;

            $u = $project->users()->where('users.id', $user->id);
            if($u->count() > 0) {
                return $u->first()->pivot->role === 2;
            }
            return false;
        });

        Gate::define('modify-file', function($user, File $file) {
            if($user->role === 2) // global admin
                return true;

            $u = $file->project->users()->where('users.id', $user->id);
            if($u->count() > 0) {
                return $u->first()->pivot->role === 2;
            }
            return false;
        });

        Gate::define('translate-text', function($user, Text $text, Language $language) {
            return Gate::forUser($user)->allows('translate-file', [$text->file, $language]);
        });

        Gate::define('translate-file', function($user, File $file, Language $language) {
            if($user->role === 2) // global admin
                return true;

            if($user->role === 1) { // developer
                $admins = $file->project->administrators()->pluck('users.id')->toArray();
                if(in_array($user->id, $admins)) {
                    return true;
                }
            }

            $languages = $user->languages->pluck('id')->toArray();
            if(in_array($language->id, $languages)) return true;

            $translators = $file->project->translators($language->id)->pluck('users.id')
                ->toArray();
            if(in_array($user->id, $translators)) {
                return true;
            }
            return false;
        });
    }
}
