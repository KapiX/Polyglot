<?php

namespace App\Http\Controllers;

use App\Models\Language;
use App\Models\Project;
use App\Http\Requests\EditProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class IndexController extends Controller
{
    function index() {
        $projects = Project::orderBy('name')->get();
        return view('index.index')
            ->with('projects', $projects);
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
        return view('help.' . $article)
            ->with('hash', $this->getCurrentCommitHash());
    }

    function profile() {
        $languages = Language::orderBy('iso_code')->get();
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

        return redirect()->route('profile');
    }

    private function getCurrentCommitHash() : string {
        $git = base_path('.git/');

        if(!File::exists($git)) {
            return '';
        }

        $head = trim(substr(file_get_contents($git . 'HEAD'), 4));
        return trim(file_get_contents($git . $head));
    }
}
