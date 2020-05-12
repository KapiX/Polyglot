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
use Polyglot\Http\Requests\AddEditFile;
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
    public function store(AddEditFile $request, Project $project)
    {
        $file = new File([
            'name' => $request->input('name'),
            'path' => '',
        ]);
        $file->checksum = '';
        $file->mime_type = '';
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
        return view('files.edit')->with('file', $file);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Polyglot\File  $file
     * @return \Illuminate\Http\Response
     */
    public function update(AddEditFile $request, File $file)
    {
        $file->name = $request->input('name');
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

        if($file->type == File::CATKEYS) {
            $file->mime_type = $instance->getMetaData(CatkeysFile::MIME_TYPE);
            $file->checksum = $instance->getMetaData(CatkeysFile::CHECKSUM);
        } else {
            $file->mime_type = 'placeholder';
            $file->checksum = 0;
        }
        $file->save();

        $textsToInsert = [];
        $idsProcessed = [];
        $idsUpdated = [];
        $translationIdsToUpdate = []; // needs_work => 1
        foreach($catkeys_processed as $catkey) {
            // these comparisons ignore case and trailing whitespace
            $text = $file->texts()
                ->whereRaw('STRCMP(text, ?) = 0', [$catkey['text']])
                ->whereRaw('STRCMP(context, ?) = 0', [$catkey['context']])
                ->whereRaw('STRCMP(comment, ?) = 0', [$catkey['comment']])
                ->selectRaw('id')
                ->selectRaw('STRCMP(BINARY text, BINARY ?) = 0 as text_same', [$catkey['text']])
                ->selectRaw('STRCMP(BINARY context, BINARY ?) = 0 as context_same', [$catkey['context']])
                ->selectRaw('STRCMP(BINARY comment, BINARY ?) = 0 as comment_same', [$catkey['comment']])
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
                // if we're here there was a match
                // let's see if texts are exactly the same
                $textToUpdate = $text->first();
                if($textToUpdate->text_same == 0 || $textToUpdate->context_same == 0 ||
                    $textToUpdate->comment_same == 0) {
                    // if not, update
                    // this is not very performant, but the assumption is
                    // that there are not a lot of changes like that
                    $textToUpdate->text = $catkey['text'];
                    $textToUpdate->context = $catkey['context'];
                    $textToUpdate->comment = $catkey['comment'];
                    $textToUpdate->save();
                    $translationIdsToUpdate = array_merge($translationIdsToUpdate,
                        $textToUpdate->translations()->pluck('id')->toArray());
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
                . ' updated (casing, whitespace changes), with '
                . count($translationIdsToUpdate)
                . ' related translations marked as incomplete.';

        return redirect()->route('files.edit', [$file->id])
            ->with('success', $result);
    }

    public function import(ImportTranslation $request, File $file, Language $lang)
    {
        $instance = $file->getFileInstance();
        $catkeys = file_get_contents($request->file('catkeys')->getRealPath());
        try {
            $catkeys_processed = $instance->process($catkeys);
        } catch(Exception $e) {
            return redirect()->route('files.translate', [$file->id, $lang->id])
                ->with('error', $e->getMessage());
        }

        if($file->type == File::CATKEYS) {
            $mimetype = $instance->getMetaData(CatkeysFile::MIME_TYPE);
            $checksum = $instance->getMetaData(CatkeysFile::CHECKSUM);
            if ($checksum !== $file->checksum || $mimetype !== $file->mime_type) {
                return redirect()->route('files.translate', [$file->id, $lang->id])
                    ->with('error', "MIME type or checksum don't match.");
            }
        }

        foreach($catkeys_processed as $catkey) {
            // the texts must match exactly, if case or whitespace are different
            // it's not the same
            $text = $file->texts()
                ->whereRaw('STRCMP(BINARY text, BINARY ?) = 0', [$catkey['text']])
                ->whereRaw('STRCMP(BINARY context, BINARY ?) = 0', [$catkey['context']])
                ->whereRaw('STRCMP(BINARY comment, BINARY ?) = 0', [$catkey['comment']])
                ->get();
            if($text->count() == 0) {
                // TODO: report stray texts?
            } else {
                // TODO: update in batches?
                $t = $text->first()
                    ->translations()->where('language_id', $lang->id)->get();
                $needswork = $catkey['text'] === $catkey['translation'] ? 1 : 0;
                if($t->count() == 0) {
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
                    $translation = $t->first();
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
            ->where('file_id', $file->id)
            ->orderBy('context', 'asc')
            ->orderBy('text', 'asc');
        if($type == 'continue') {
            $translations = $translations->having('needs_work', 1)
                ->get();
            $translationsCount = $translations->count();
            $translations = $translations->forPage(1, $perPage);
        }
        else
            $translations = $translations->paginate($perPage);

        return view('files.translate')
            ->with('perPage', $perPage)
            ->with('type', $type)
            ->with('file', $file)
            ->with('lang', $lang)
            ->with('allTranslationsCount', $translationsCount)
            ->with('translations', $translations);
    }

    public function export(File $file, Language $lang)
    {
        $catkeys = $this->getCatkeysFile($file, $lang);
        if($catkeys === null)
            return \Redirect::route('projects.show', [$file->project_id])
                ->with('message', 'Checksum or MIME type are missing.');

        // prepare file
        $filename = $lang->iso_code . '.' . $file->getFileInstance()->getExtension();
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
        if($file->checksum == null || $file->mime_type == null)
            return null;

        $files = [];
        $languages = Language::whereIn('id',
            Translation::whereIn('text_id', $file->texts()->select('id')->getQuery())
                ->distinct()->select('language_id')->getQuery()
        )->get();
        foreach($languages as $lang) {
            $files[$lang->iso_code] = $this->getCatkeysFile($file, $lang);
        }

        $filename = sprintf('%s_%s_%s.zip', $file->project->name, $file->name, $file->checksum);
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
        foreach($files as $name => $file) {
            $zip->addFile(storage_path('app/' . $file), $name . '.' . $extension);
        }
        $zip->close();

        return response()->download($tmpfile, $filename, $headers)->deleteFileAfterSend(true);
    }

    private function getCatkeysFile(File $file, Language $lang)
    {
        if($file->checksum == null || $file->mime_type == null)
            return null;

        $lastUpdated = Translation::lastUpdatedAt($file->id, $lang->id);
        if($lastUpdated === null) $lastUpdated = '1970-01-01 00:00:01';
        else $lastUpdated = $lastUpdated->updated_at;

        $instance = $file->getFileInstance();

        // see if we have a cached copy
        $directory = sprintf('exported/%u/%u', $file->id, $lang->id);
        $escapedMIME = str_replace('/', '_', $file->mime_type);
        $filename = sprintf('%s/%s_%s_%s.%s', $directory, $file->checksum, $escapedMIME, $lastUpdated, $instance->getExtension());
        if(Storage::exists($filename) == false) {
            // we don't, delete old ones and generate new
            Storage::delete(Storage::files($directory));

            $texts = $file->texts()->get()->groupBy('context');
            $translations = Translation::where('language_id', $lang->id)
                ->whereIn('text_id', $file->texts()->select('id')->getQuery())
                ->get()->groupBy('text_id');
            if($file->type == File::CATKEYS) {
                $instance->setMetaData(CatkeysFile::LANGUAGE, $lang->name);
                $instance->setMetaData(CatkeysFile::MIME_TYPE, $file->mime_type);
                $instance->setMetaData(CatkeysFile::CHECKSUM, $file->checksum);
            }
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
            $result = $instance->assemble($keys);
            Storage::put($filename, $result);
        }
        return $filename;
    }
}
