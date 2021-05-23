<?php

namespace App\Policies;

use App\Models\GlossaryEntry;
use App\Models\User;
use App\Models\Language;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Http\Request;

class GlossaryEntryPolicy
{
    use HandlesAuthorization;

    private $glossary;

    public function __construct(Request $request)
    {
        // workaround for authorizeResource not understanding nested resources
        $this->glossary = $request->route('glossary');
    }

    public function before(User $user, $ability)
    {
        if($user->isAdministrator()) {
            return true;
        }
        return null;
    }

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function viewAny(?User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GlossaryEntry  $glossaryEntry
     * @return mixed
     */
    public function view(?User $user, GlossaryEntry $glossaryEntry)
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        $languages = $user->languages->pluck('id')->toArray();
        return in_array($this->glossary->id, $languages);
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GlossaryEntry  $glossaryEntry
     * @return mixed
     */
    public function update(User $user, GlossaryEntry $entry)
    {
        $languages = $user->languages->pluck('id')->toArray();
        return in_array($this->glossary->id, $languages);
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\GlossaryEntry  $glossaryEntry
     * @return mixed
     */
    public function delete(User $user, GlossaryEntry $entry)
    {
        $languages = $user->languages->pluck('id')->toArray();
        return in_array($this->glossary->id, $languages);
    }
}
