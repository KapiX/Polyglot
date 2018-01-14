<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\Text;
use Polyglot\Translation;
use Illuminate\Http\Request;

class TextsController extends Controller
{
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
            $translation->author_id = 1; // FIXME
            $translation->translation = $request->post('translation');
            $translation->needs_work = 0; // FIXME
            $translation->save();
        } else {
            $translation = $translation->first();
            $translation->author_id = 1; // FIXME
            $translation->translation = $request->post('translation');
            $translation->needs_work = 0; // FIXME
            $translation->save();
        }
        return \Response::json(['status' => 'success', 'translation'=>$translation->translation]);
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
        $text = '';
        if($translation->count() > 0) {
            $text = $translation->first()->translation;
        }
        $response = ['translation' => $text];

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
