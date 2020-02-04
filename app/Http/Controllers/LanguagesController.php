<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Http\Requests\AddLanguage;
use Polyglot\Http\Requests\EditLanguage;
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


    public function update(EditLanguage $request, Language $language)
    {
        // _$id suffix is a workaround, Form::text uses value parameter as a hint,
        // if $request[key] exists it will override provided value
        $language->name = $request->input('name_' . $language->id);
        $language->iso_code = $request->input('iso_code_' . $language->id);
        $language->style_guide_url = $request->input('style_guide_url_' . $language->id);
        $language->terminology_url = $request->input('terminology_url_' . $language->id);
        $language->save();

        return redirect()->route('languages.index')
            ->with('message', 'Language saved.');
    }
}
