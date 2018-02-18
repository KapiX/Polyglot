<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\User;
use Polyglot\Http\Requests\EditProfile;
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
        $user = Auth::user();
        return view('index.profile')
            ->with('user', $user);
    }

    function updateProfile(EditProfile $request) {
        $user = Auth::user();
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        return redirect('profile');
    }
}
