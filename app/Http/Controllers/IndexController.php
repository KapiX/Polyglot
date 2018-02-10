<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\User;
use Illuminate\Http\Request;

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

    function settings() {
        $languages = Language::all();
        $users = User::paginate(10);
        $roles = [ 0 => 'User', 1 => 'Developer', 2 => 'Admin' ];
        return view('index.settings')
            ->with('languages', $languages)
            ->with('users', $users)
            ->with('roles', $roles);
    }

    function addLanguage(Request $request) {
        $language = new Language;
        $language->iso_code = $request->input('iso');
        $language->name = $request->input('name');
        $language->save();

        return \Redirect::route('settings')
            ->with('message', 'Language added.');
    }

    function changeRole(Request $request, User $user) {
        $user->role = (integer) $request->get('role')[0];
        $user->save();

        return \Redirect::route('settings')
            ->with('message', 'Role updated.');
    }
}
