<?php

namespace Polyglot\Http\Controllers;

use Exception;
use Polyglot\CatkeysFile;
use Polyglot\TranslationFile;
use ZipArchive;
use Polyglot\File;
use Polyglot\Language;
use Polyglot\Project;
use Polyglot\Text;
use Polyglot\Translation;
use Polyglot\Http\Requests\AddFile;
use Polyglot\Http\Requests\EditFile;
use Polyglot\Http\Requests\ImportTranslation;
use Polyglot\Http\Requests\UploadFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int                       $project_id
     * @return \Illuminate\Http\Response
     */
    public function store(AddFile $request, Project $project)
    {
        $file = new File([
            'name' => $request->input('name'),
            'type' => (integer) $request->input('type')[0]
        ]);
        $editableMetaData = $file->getFileInstance()->editableMetaData();
        if(empty($editableMetaData) == false) {
            $file->metadata = array_fill_keys($editableMetaData, null);
        }
        $file->project_id = $project->id;

        if($file->save()) {
            return redirect()->route('files.edit', [$file->id]);
        } else {
            return redirect()->route('projects.show', [$project->id]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function edit(File $file)
    {
        $filename = str_replace('%lang%', 'en', basename($file->path));
        return view('files.edit')->with('file', $file)->with('filename', $filename);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(EditFile $request, File $file)
    {
        $file->name = $request->input('name');
        $file->path = $request->input('path');

        $metaData = $file->metadata;
        $instance = $file->getFileInstance();
        foreach($instance->editableMetaData() as $data) {
            $metaData[$data] = $request->input($data);
        }
        $file->metadata = $metaData;
        $file->save();

        return redirect()->back();
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
    public function upload(UploadFile $request, File $file)
    {
        $instance = $file->getFileInstance();
        $catkeys = file_get_contents($request->file('catkeys')->getRealPath());
        try {
            $catkeys_processed = $instance->process($catkeys);
        } catch(Exception $e) {
            return redirect()->route('files.edit', [$file->id])
                ->with('error', $e->getMessage());
        }

        $matchColumns = $instance->matchTextsBy();
        if(empty($matchColumns)) {
            throw new \Exception('This file type has no match columns. This is programming error.');
        }
        $indexColumn = $instance->indexColumn();

        $file->metadata = $instance->getMetaData();
        $file->save();

        $textsToInsert = [];
        $idsProcessed = [];
        $idsUpdated = [];
        $translationIdsToUpdate = []; // needs_work => 1
        foreach($catkeys_processed as $catkey) {
            // these comparisons ignore case and trailing whitespace
            // selectRaw must be used here, select overwrites previous calls
            $query = $file->texts()
                ->selectRaw('id');
            if($indexColumn !== null) {
                $query->selectRaw($indexColumn);
            }
            foreach($matchColumns as $column) {
                $query->whereRaw('STRCMP(' . $column . ', ?) = 0', [$catkey[$column]])
                    ->selectRaw('STRCMP(BINARY ' . $column . ', BINARY ?) = 0 as ' . $column . '_same', [$catkey[$column]]);
            }
            $text = $query->get();
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
                // if we're here there was a match
                // let's see if texts are exactly the same
                $textToUpdate = $text->first();
                $catkeyChanged = false;
                $indexChanged = false;
                foreach($matchColumns as $column) {
                    $catkeyChanged = $catkeyChanged || $textToUpdate[$column . '_same'] == 0;
                }
                if($indexColumn !== null) {
                    $indexChanged = $textToUpdate[$indexColumn] != $catkey[$indexColumn];
                }
                if($catkeyChanged || $indexChanged) {
                    // if not, update
                    // this is not very performant, but the assumption is
                    // that there are not a lot of changes like that
                    $textToUpdate->text = $catkey['text'];
                    $textToUpdate->context = $catkey['context'];
                    $textToUpdate->comment = $catkey['comment'];
                    $textToUpdate->save();
                    if ($catkeyChanged == true) {
                        $translationIdsToUpdate = array_merge($translationIdsToUpdate,
                            $textToUpdate->translations()->pluck('id')->toArray());
                    }
                    $idsUpdated[] = $textToUpdate->id;
                }
                // if it is in the table, remember it
                // later we can pull all ids and diff with them to see which
                // catkeys disappeared from the file
                $idsProcessed[] = $textToUpdate->id;
            }
        }
        $allIds = $file->texts()->pluck('id')->toArray();
        $deleteIds = array_values(array_diff($allIds, $idsProcessed));
        if(!empty($deleteIds))
            Text::whereIn('id', $deleteIds)->delete();
        if(!empty($textsToInsert))
            Text::insert($textsToInsert);
        if(!empty($translationIdsToUpdate))
            Translation::whereIn('id', $translationIdsToUpdate)->update(['needs_work' => 1]);
        $result = 'Catkeys uploaded. ' . count($textsToInsert) . ' added, '
                . count($deleteIds) . ' deleted, ' . count($idsUpdated)
                . ' updated (order, casing, whitespace changes), with '
                . count($translationIdsToUpdate)
                . ' related translations marked as incomplete.';

        return redirect()->route('files.edit', [$file->id])
            ->with('success', $result);
    }

    public function import(ImportTranslation $request, File $file, Language $lang)
    {
        $instance = $file->getFileInstance();
        $oldMetadata = $instance->getMetaData();
        $catkeys = file_get_contents($request->file('catkeys')->getRealPath());
        try {
            $catkeys_processed = $instance->process($catkeys);
        } catch(Exception $e) {
            return redirect()->route('files.translate', [$file->id, $lang->id])
                ->with('error', $e->getMessage());
        }

        if($instance->validateMetaData($oldMetadata) == false) {
            return redirect()->route('files.translate', [$file->id, $lang->id])
                ->with('error', "Metadata does not match.");
        }

        $matchColumns = $instance->matchTranslationsBy();
        if(empty($matchColumns)) {
            throw new \Exception('This file type has no match columns. This is programming error.');
        }
        foreach($catkeys_processed as $catkey) {
            // the texts must match exactly, if case or whitespace are different
            // it's not the same
            $text = $file->texts();
            foreach($matchColumns as $column) {
                $text->whereRaw('STRCMP(BINARY ' . $column . ', BINARY ?) = 0', [$catkey[$column]]);
            }
            $text->get();
            if($text->count() == 0) {
                // TODO: report stray texts?
            } else {
                // TODO: update in batches?
                $tr = $text->first()
                    ->translations()->where('language_id', $lang->id)->get();
                $needswork = $text->first()->text === $catkey['translation'] ? 1 : 0;
                if($tr->count() == 0) {
                    if($needswork == 0) {
                        $translation = new Translation;
                        $translation->text_id = $text->first()->id;
                        $translation->language_id = $lang->id;
                        $translation->author_id = Auth::id();
                        $translation->translation = $catkey['translation'];
                        $translation->needs_work = $needswork;
                        $translation->save();
                    }
                } else {
                    $translation = $tr->first();
                    $translation->author_id = Auth::id();
                    $translation->translation = $catkey['translation'];
                    $translation->needs_work = $needswork;
                    $translation->save();
                }
            }
        }

        $users = $file->project->users();
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

        return redirect()->route('files.translate', [$file->id, $lang->id])
            ->with('success', 'Translations uploaded.');
    }

    public function translate(File $file, Language $lang, $type = 'all')
    {
        $perPage = 30;
        $translationsCount = 0;
        $translations = Text::select('texts.id as text_id', 'file_id', 'text', 'comment', 'context')
            ->selectRaw('COALESCE(`language_id`, ?) as `language_id`', [$lang->id])
            ->selectRaw('COALESCE(`translation`, `text`) as `translation`')
            ->selectRaw('COALESCE(`needs_work`, 1) as `needs_work`')
            ->leftJoin('translations', function($join) use($lang) {
                $join->on('texts.id', '=', 'translations.text_id')
                    ->where('language_id', $lang->id);
            })
            ->where('file_id', $file->id);
        $instance = $file->getFileInstance();
        if($instance->indexColumn() !== null) {
            $translations->orderByRaw('cast(' . $instance->indexColumn() . ' as int) asc');
        } else {
            $translations->orderBy('context', 'asc')
                ->orderBy('text', 'asc');
        }
        if($type == 'continue') {
            $translations = $translations->having('needs_work', 1)
                ->get();
            $translationsCount = $translations->count();
            $translations = $translations->forPage(1, $perPage);
        }
        else
            $translations = $translations->paginate($perPage);
        $filename = str_replace('%lang%', $lang->iso_code, basename($file->path));

        return view('files.translate')
            ->with('perPage', $perPage)
            ->with('type', $type)
            ->with('file', $file)
            ->with('lang', $lang)
            ->with('allTranslationsCount', $translationsCount)
            ->with('translations', $translations)
            ->with('filename', $filename);
    }

    public function export(File $file, Language $lang)
    {
        $catkeys = $this->getCatkeysFile($file, $lang);
        if($catkeys === null)
            return \Redirect::route('projects.show', [$file->project_id])
                ->with('message', 'Checksum or MIME type are missing.');

        // prepare file
        $basename = basename($file->path);
        $filename = str_replace('%lang%', $lang->iso_code, $basename)
            . '.' . $file->getFileInstance()->getExtension();
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'text/plain',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];
        $callback = function() use($catkeys) {
            $file = fopen('php://output', 'w');
            fwrite($file, Storage::get($catkeys));
            fclose($file);
        };

        return \Response::stream($callback, 200, $headers);
    }

    public function exportAll(File $file)
    {
        $files = [];
        $languages = Language::whereIn('id',
            Translation::whereIn('text_id', $file->texts()->select('id')->getQuery())
                ->distinct()->select('language_id')->getQuery()
        )->get();
        foreach($languages as $lang) {
            $files[$lang->iso_code] = $this->getCatkeysFile($file, $lang);
        }
        if(empty($files))
            return redirect()->back();

        $filename = sprintf('%s_%s.zip', $file->project->name, $file->name);
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];
        $tmpfile = tempnam(storage_path('app'), 'zip');
        $extension = $file->getFileInstance()->getExtension();
        $zip = new ZipArchive();
        $zip->open($tmpfile, ZipArchive::CREATE);
        foreach($files as $lang => $generatedPath) {
            $path = str_replace('%lang%', $lang, $file->path);
            $zip->addFile(storage_path('app/' . $generatedPath), $path . '.' . $extension);
        }
        $zip->close();

        return response()->download($tmpfile, $filename, $headers)->deleteFileAfterSend(true);
    }

    private function getCatkeysFile(File $file, Language $lang)
    {
        $lastUpdated = Translation::lastUpdatedAt($file->id, $lang->id);
        if($lastUpdated === null)
            $lastUpdated = '1970-01-01 00:00:01';
        else
            $lastUpdated = $lastUpdated->updated_at;

        $instance = $file->getFileInstance();
        // see if we have a cached copy
        $directory = sprintf('exported/%u/%u', $file->id, $lang->id);
        $filename = sprintf('%s/%s.%s', $directory, $lastUpdated, $instance->getExtension());
        if(Storage::exists($filename) == false) {
            // we don't, delete old ones and generate new
            Storage::delete(Storage::files($directory));

            $texts = $file->texts()->get()->groupBy('context');
            $translations = Translation::where('language_id', $lang->id)
                ->whereIn('text_id', $file->texts()->select('id')->getQuery())
                ->get()->groupBy('text_id');
            $keys = [];
            foreach($texts as $context) {
                foreach($context as $text) {
                    $t = $translations->get($text['id']);
                    if($t !== null)
                        $translation = $t[0]['translation'];
                    else
                        $translation = $text['text'];
                    $keys[] = [
                        'text' => $text['text'],
                        'context' => $text['context'],
                        'comment' => $text['comment'],
                        'translation' => $translation
                    ];
                }
            }
            $instance->setLanguage($lang->name);
            $result = $instance->assemble($keys);
            Storage::put($filename, $result);
        }
        return $filename;
    }
}
