<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Illuminate\Http\Request;

class LanguagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $languages = Language::orderBy('name')->get();
        return view('languages.index')
            ->with('languages', $languages);
    }

    public function store(Request $request) {
        $language = new Language;
        $language->iso_code = $request->input('iso_code');
        $language->name = $request->input('name');
        $language->save();

        return \Redirect::route('languages.index')
            ->with('message', 'Language added.');
    }


    public function update(Request $request, Language $language)
    {
        $language->name = $request->input('name');
        $language->iso_code = $request->input('iso_code');
        $language->save();

        return \Redirect::route('languages.index')
            ->with('message', 'Language saved.');
    }
}
