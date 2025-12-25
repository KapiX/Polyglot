<?php

namespace App\Policies;

use App\Models\File;
use App\Models\Language;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class FilePolicy
{
    private $project;
    private $language;

    public function __construct(Request $request)
    {
        // workaround for authorizeResource not understanding nested resources
        $this->project = $request->route('project');
        $this->language = $request->route('language');
    }

    public function before(User $user, string $ability): bool|null
    {
        if($user->isAdministrator()) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, File $file): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->project->administrators->contains($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, File $file): bool
    {
        return $file->project->administrators->contains($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, File $file): bool
    {
        return $file->project->administrators->contains($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, File $file): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, File $file): bool
    {
        return false;
    }

    public function translate(User $user, File $file, Language $language): bool
    {
        if($user->isDeveloper() && $file->project->administrators->firstWhere('id', $user->id) != null)
            return true;

        if ($user->languages->firstWhere('id', $language->id) != null)
            return true;

        if($file->project->translators
                ->mapToGroups(fn($item, $key) => [$item['pivot']['language_id'] => $item['id']])
                ->firstWhere($language->id, $user->id) != null)
            return true;

        return false;
    }
}
