<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\User;
use Barryvdh\Debugbar\Facades\Debugbar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    const ROLES = [
        User::ROLE_USER => 'User',
        User::ROLE_DEVELOPER => 'Developer',
        User::ROLE_ADMIN => 'Admin'
    ];

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        if($search == '') {
            $users = User::paginate(15);
        } else {
            $users = User::search($search)->paginate(15);
        }
        return view('users.index')
            ->with('users', $users)
            ->with('roles', self::ROLES)
            ->with('search', $search);
    }

    public function edit(User $user)
    {
        if($user->id == Auth::user()->id)
            return \Redirect::route('users.index');

        $languages = Language::orderBy('iso_code')->pluck('name', 'id');
        return view('users.edit')
            ->with('user', $user)
            ->with('roles', self::ROLES)
            ->with('languages', $languages);
    }

    public function update(Request $request, User $user)
    {
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->role = (integer) $request->input('role')[0];
        $user->save();

        $languages = $request->input('languages', []);
        $user->languages()->sync($languages);

        return redirect()->route('users.index')
            ->with('success', 'User data saved.');
    }
}
