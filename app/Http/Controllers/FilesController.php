<?php

namespace Polyglot\Http\Controllers;

use Polyglot\File;
use Polyglot\Language;
use Polyglot\Text;
use Polyglot\Translation;
use Polyglot\Http\Requests\FileFormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilesController extends Controller
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
     * @param  int                       $project_id
     * @return \Illuminate\Http\Response
     */
    public function store(FileFormRequest $request)
    {
        $file = new File([
            'name' => $request->input('name'),
            'path' => '',
        ]);
        $file->checksum = '';
        $file->project_id = $request->get('project_id');

        $file->save();

        return \Redirect::route('projects.show', [$request->get('project_id')])
            ->with('message', 'File successfully added.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function show(File $file)
    {
        return view('files.show')->with('file', $file);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function destroy(File $file)
    {
        //
    }

    /**
     * Upload the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function upload(Request $request, File $file)
    {
        $catkeys = file_get_contents($request->file('catkeys')->getRealPath());
        [$mimetype, $checksum, $catkeys_processed] = $this->processCatkeysFile($catkeys);

        $file->mime_type = $mimetype;
        $file->checksum = $checksum;
        $file->save();

        $textsToInsert = [];
        $idsProcessed = [];
        foreach($catkeys_processed as $catkey) {
            $text = $file->texts()
                ->whereRaw('STRCMP(text, ?) = 0', [$catkey['text']])
                ->whereRaw('STRCMP(context, ?) = 0', [$catkey['context']])
                ->whereRaw('STRCMP(comment, ?) = 0', [$catkey['comment']])
                ->get();
            if($text->count() == 0) {
                $textToInsert = [
                    'file_id' => $file->id,
                    'text' => $catkey['text'],
                    'context' => $catkey['context'],
                    'comment' => $catkey['comment'],
                    'created_at' => new \DateTime(),
                    'updated_at' => new \DateTime()
                ];
                $textsToInsert[] = $textToInsert;
            } else {
                // if it is in the table, remember it
                // later we can pull all ids and diff with them to see which
                // catkeys disappeared from the file
                $idsProcessed[] = $text->first()->id;
            }
        }
        $allIds = $file->texts()->pluck('id')->toArray();
        $deleteIds = array_values(array_diff($allIds, $idsProcessed));
        if(!empty($deleteIds))
            Text::whereIn('id', $deleteIds)->delete();
        if(!empty($textsToInsert))
            Text::insert($textsToInsert);

        // TODO: how many added and deleted
        return \Redirect::route('files.show', [$file->id])->with('message', 'Catkeys uploaded.');
    }

    public function translate(File $file, Language $lang)
    {
        $translations = Translation::where('language_id', $lang->id)->get();
        return view('files.translate')->with('file', $file)->with('lang', $lang)->with('translations', $translations->groupBy('text_id'));
    }

    private function processCatkeysFile($contents)
    {
        $separator = "\r\n";
        $line = strtok($contents, $separator);

        $catkeys = [];
        $first = explode("\t", $line);
        $mimetype = $first[2];
        $checksum = $first[3];
        $line = strtok($separator);

        while($line !== false) {
            $exploded = explode("\t", $line);
            $catkeys[] = [
                'text' => $exploded[0],
                'context' => $exploded[1],
                'comment' => $exploded[2],
                'translation' => $exploded[3]
            ];
            $line = strtok($separator);
        }
        return [$mimetype, $checksum, $catkeys];
    }
}
