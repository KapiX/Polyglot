<?php

namespace App\Policies;

use App\Models\File;
use App\Models\Language;
use App\Models\Text;
use App\Models\User;
use Illuminate\Http\Request;

class TextPolicy
{
    private $text;
    private $language;

    public function __construct(Request $request)
    {
        $this->text = $request->route('text');
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
    public function view(User $user, Text $text): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->text->file->project->administrators->contains($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Text $text): bool
    {
        return $text->file->project->administrators->contains($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Text $text): bool
    {
        return $text->file->project->administrators->contains($user);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Text $text): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Text $text): bool
    {
        return false;
    }

    public function translate(User $user, Text $text, Language $language) {
        return $user->can('translate', [$text->file, $language]);
    }
}
