<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\Text;
use Polyglot\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TextsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Text $text, Language $lang)
    {
        $translation = $text->translations()->where('language_id', $lang->id)->get();
        if($translation->count() === 0) {
            $translation = new Translation();
            $translation->text_id = $text->id;
            $translation->language_id = $lang->id;
            $translation->author_id = Auth::id();
            $translation->translation = $request->post('translation');
            $translation->needs_work = $request->post('needswork') === 'true' ? 1 : 0;
            $translation->save();
        } else {
            $translation = $translation->first();
            $translation->author_id = Auth::id();
            $translation->translation = $request->post('translation');
            $translation->needs_work = $request->post('needswork') === 'true' ? 1 : 0;
            $translation->save();
        }

        $users = $text->file->project->users();
        $isInDb = $users->where('user_id', Auth::id())
                        ->where(function($query) use ($lang) {
            $query->where('project_user.role', 2)
                  ->orWhere('project_user.language_id', $lang->id);
        })->get();
        if($isInDb->count() == 0) {
            $users->attach([
                Auth::id() => ['language_id' => $lang->id, 'role' => 1]
            ]);
        }
        return \Response::json(['status' => 'success', 'translation' => $translation->translation, 'needswork' => $translation->needs_work === 1 ? true : false]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \Polyglot\Text  $text
     * @return \Illuminate\Http\Response
     */
    public function show(Text $text, Language $lang)
    {
        $translation = $text->translations()->where('language_id', $lang->id)->get();
        $text = $text->text;
        $needswork = true;
        if($translation->count() > 0) {
            $text = $translation->first()->translation;
            $needswork = $translation->first()->needs_work === 1 ? true : false;
        }
        $response = ['translation' => $text, 'needswork' => $needswork];

        return \Response::json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Polyglot\Text  $text
     * @return \Illuminate\Http\Response
     */
    public function edit(Text $text)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Polyglot\Text  $text
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Text $text)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Polyglot\Text  $text
     * @return \Illuminate\Http\Response
     */
    public function destroy(Text $text)
    {
        //
    }
}
