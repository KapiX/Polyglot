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
}
