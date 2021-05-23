<?php

namespace App\Http\Controllers;

use App\Models\GlossaryEntry;
use App\Models\Language;
use App\Http\Requests\AddGlossaryEntry;
use App\Http\Requests\EditGlossaryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GlossaryEntryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(GlossaryEntry::class, 'entry');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $preferred_languages = Auth::user()->preferred_languages ?? [];
        $columns = ['id', 'name', 'iso_code'];
        $glossaries = GlossaryEntry::glossaries(
            Language::allWithPrioritized($preferred_languages, $columns),
            $columns)->get();

        return view('glossaries.list')
            ->with('glossaries', $glossaries);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \App\Models\Language  $language
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Language $glossary)
    {
        $search = $request->input('search');
        $entries = GlossaryEntry::select('*')
            ->selectRaw('UPPER(SUBSTRING(text, 1, 1)) as letter')
            ->where('language_id', $glossary->id)
            ->orderBy('text');
        if($search != '') {
            $entries->search($search);
        }

        return view('glossaries.index')
            ->with('glossary', $glossary)
            ->with('entries', $entries->paginate(300))
            ->with('search', $search);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \App\Models\Language  $language
     * @return \Illuminate\Http\Response
     */
    public function create(Language $glossary)
    {
        return view('glossaries.create')
            ->with('glossary', $glossary);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Language  $language
     * @return \Illuminate\Http\Response
     */
    public function store(AddGlossaryEntry $request, Language $glossary)
    {
        $text = $request->input('text');
        $translation = $request->input('translation');

        $texts = collect($text);
        $translations = $translation;

        $data = $texts->zip($translations);
        $data->transform(function ($item, $key) use ($glossary) {
            return [
                'author_id' => Auth::id(),
                'language_id' => $glossary->id,
                'text' => $item[0],
                'translation' => $item[1]
            ];
        });
        $adding = $data->count();
        $data = $data->reject(function ($value, $key) {
            return $value['text'] === null || $value['text'] === ''
                || $value['translation'] === null || $value['translation'] === '';
        });
        $addingSanitized = $data->count();

        if(GlossaryEntry::insert($data->toArray()) === true) {
            return redirect()->route('glossaries.entries.index', [$glossary])
                ->with('success', 'Added ' . $addingSanitized . ' glossary entries with '
                    . ($adding - $addingSanitized) . ' entries rejected for having empty fields.');
        } else {
            return redirect()->back()->withErrors(['Adding entries failed.']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Language  $language
     * @param  \App\Models\GlossaryEntry  $glossaryEntry
     * @return \Illuminate\Http\Response
     */
    public function edit(Language $glossary, GlossaryEntry $entry)
    {
        return view('glossaries.edit')
            ->with('glossary', $glossary)
            ->with('entry', $entry);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Language  $language
     * @param  \App\Models\GlossaryEntry  $glossaryEntry
     * @return \Illuminate\Http\Response
     */
    public function update(EditGlossaryEntry $request, Language $glossary, GlossaryEntry $entry)
    {
        $entry->author_id = Auth::id();
        $entry->text = $request->input('text');
        $entry->translation = $request->input('translation');
        $entry->save();

        return redirect()->route('glossaries.entries.index', [$glossary])
            ->with('success', 'Entry updated.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Language  $language
     * @param  \App\Models\GlossaryEntry  $glossaryEntry
     * @return \Illuminate\Http\Response
     */
    public function destroy(Language $glossary, GlossaryEntry $entry)
    {
        $entry->delete();

        return redirect()->route('glossaries.entries.index', [$glossary])
            ->with('success', 'Entry deleted.');
    }
}
