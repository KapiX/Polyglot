<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    function index() {
        return view('index.index');
    }

    function settings() {
        $languages = Language::all();
        return view('index.settings')->with('languages', $languages);
    }

    function addLanguage(Request $request) {
        $language = new Language;
        $language->iso_code = $request->input('iso');
        $language->name = $request->input('name');
        $language->save();

        return \Redirect::route('settings')->with('message', 'Language added.');
    }
}
