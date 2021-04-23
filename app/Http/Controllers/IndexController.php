<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\User;
use App\Http\Requests\EditProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IndexController extends Controller
{
    function index() {
        return view('index.index');
    }

    function login() {
        return view('index.login');
    }

    function help($article = 'index') {
        $allowed = [
            'index',
            'user',
            'developer',
        ];
        if(!in_array($article, $allowed))
            $article = 'index';
        return view('help.' . $article);
    }

    function profile() {
        $languages = Language::orderBy('name')->pluck('name', 'id');
        $user = Auth::user();
        return view('index.profile')
            ->with('user', $user)
            ->with('languages', $languages);
    }

    function updateProfile(EditProfile $request) {
        $user = Auth::user();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->preferred_languages = $request->input('languages');
        $user->save();

        return redirect('profile');
    }
}
