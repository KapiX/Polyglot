<?php

namespace App\Policies;

use App\Models\Language;
use App\Models\Text;
use App\Models\User;
use Illuminate\Http\Request;

class TextPolicy
{
    public function translate(User $user, Text $text, Language $language) {
        return $user->can('translate', [$text->file, $language]);
    }
}
