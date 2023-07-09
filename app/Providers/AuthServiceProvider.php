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
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        App\Models\GlossaryEntry::class => App\Policies\GlossaryEntryPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('global-settings', function($user) {
            return $user->role === 2; // global admin
        });

        Gate::define('add-project', function($user) {
            return $user->role === 1 || $user->role === 2; // developer
        });

        Gate::define('modify-project', function($user, Project $project) {
            if($user->role === 2) // global admin
                return true;

            if($user->role === 1) {
                $admins = $project->administrators->pluck('id')->toArray();
                if(in_array($user->id, $admins)) {
                    return true;
                }
            }
            return false;
        });

        Gate::define('modify-file', function($user, File $file) {
            return Gate::forUser($user)->allows('modify-project', $file->project);
        });

        Gate::define('translate-text', function($user, Text $text, Language $language) {
            return Gate::forUser($user)->allows('translate-file', [$text->file, $language]);
        });

        Gate::define('translate-file', function($user, File $file, Language $language) {
            if($user->role === 2) // global admin
                return true;

            if($user->role === 1) { // developer
                $admins = $file->project->administrators->pluck('id')->toArray();
                if(in_array($user->id, $admins)) {
                    return true;
                }
            }

            $languages = $user->languages->pluck('id')->toArray();
            if(in_array($language->id, $languages)) return true;

            $translators = $file->project->translators->mapToGroups(function ($item, $key) {
                return [$item['pivot']['language_id'] => $item['id']];
            })->toArray();
            if(in_array($user->id, $translators[$language->id] ?? [])) {
                return true;
            }
            return false;
        });

        Gate::define('modify-glossary', function($user, Language $language) {
            if($user->role === User::ROLE_ADMIN)
                return true;
            
            $languages = $user->languages->pluck('id')->toArray();
            return in_array($language->id, $languages);
        });
    }
}
