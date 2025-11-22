<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddLanguage;
use App\Http\Requests\EditLanguage;
use App\Models\Language;
use Illuminate\Http\Request;

class LanguagesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $addLanguage = new Language;
        $addLanguage->name = '_Add';
        $languages = Language::orderBy('name')->get();
        $languages->prepend($addLanguage);
        return view('languages.index')
            ->with('languages', $languages);
    }

    public function create() {
        return view('languages.create');
    }

    public function store(AddLanguage $request) {
        $language = new Language;
        $language->iso_code = $request->input('iso_code');
        $language->name = $request->input('name');
        $language->style_guide_url = $request->input('style_guide_url');
        $language->terminology_url = $request->input('terminology_url');
        $language->save();

        return redirect()->route('languages.index')
            ->with('message', 'Language added.');
    }

    public function edit(Language $language)
    {
        return view('languages.edit')->with('language', $language);
    }

    public function update(EditLanguage $request, Language $language)
    {
        $language->iso_code = $request->input('iso_code');
        $language->name = $request->input('name');
        $language->style_guide_url = $request->input('style_guide_url');
        $language->terminology_url = $request->input('terminology_url');
        $language->save();

        return redirect()->route('languages.index')
            ->with('message', 'Language saved.');
    }
}
