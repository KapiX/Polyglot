<?php

namespace App\Http\Controllers;

use ZipArchive;
use App\Models\File;
use App\Models\Language;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\Text;
use App\Models\Translation;
use App\Http\Requests\AddProject;
use App\Http\Requests\EditProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProjectsController extends Controller
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
        $projects = Project::orderBy('name')->get();
        $preferred_languages = Auth::user()->preferred_languages ?? [];
        $project_needs_work = null;
        if(!empty($preferred_languages)) {
            $project_needs_work = Project::allNeedsWorkForLanguages($preferred_languages)->get()
                ->pluck('needs_work', 'id');
        }
        return view('projects.index')
            ->with('projects', $projects)
            ->with('project_needs_work', $project_needs_work);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AddProject $request)
    {
        $project = new Project;
        $project->name = $request->input('name');
        $project->save();

        $project->users()->attach(Auth::id(), ['role' => 2]);

        if($project->save()) {
            return redirect()->route('projects.edit', [$project->id]);
        } else {
            return redirect()->route('projects.index');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, $display = 'active')
    {
        $preferred_languages = Auth::user()->preferred_languages ?? [];
        $languages = Language::allWithPrioritized(
            $preferred_languages, ['id', 'name', 'iso_code'])->get();

        // count progress
        $status = [];
        $modified = [];
        foreach($project->files as $file) {
            $status[$file->id] = [];
            $texts = $file->texts()->select('id');
            $count = $texts->count();
            $translations = Translation::whereIn('text_id', $texts->getQuery())
                ->select('language_id', 'needs_work')
                ->selectRaw('count(id) as count')
                ->groupBy('language_id', 'needs_work');
            $file_status = DB::table(DB::raw("({$translations->toSql()}) as temp"))
                ->mergeBindings($translations->getQuery())
                ->select('language_id')
                ->selectRaw('max(case when needs_work = 0 then temp.count else 0 end) translated')
                ->selectRaw('max(case when needs_work = 1 then temp.count else 0 end) needs_work')
                ->groupBy('language_id')
                ->get()
                ->mapWithKeys(function($item) use ($count) {
                    return [
                        $item->language_id => [
                            'needs_work' => floor($item->needs_work / $count * 100),
                            'translated' => floor($item->translated / $count * 100)
                        ]
                    ];
                })->toArray();
            foreach($languages as $language) {
                $lang_status = ['needs_work' => 0, 'translated' => 0];
                if(array_key_exists($language->id, $file_status))
                    $lang_status = $file_status[$language->id];

                $status[$file->id][$language->id] = $lang_status;

                if(array_key_exists($language->id, $modified) === false)
                    $modified[$language->id] = 0;
                $modified[$language->id] += array_sum($lang_status);
            }
        }
        $contributorRoles = [
            0 => 'past-translator',
            1 => 'translator',
            2 => 'admin'
        ];
        $contributors = ProjectUser::contributors($project->id)
            ->with('user')
            ->with('language')
            ->get()->groupBy('language_id')->sortBy(function($c) {
                return strtolower($c[0]->language->name);
            });
        $displayLinkLabel = sprintf('Show %s languages',
                                    ($display == 'all' ? 'only active' : 'all'));
        return view('projects.show')
            ->with('display', $display)
            ->with('project', $project)
            ->with('status', $status)
            ->with('modifiedKeys', $modified)
            ->with('displayLinkLabel', $displayLinkLabel)
            ->with('languages', $languages)
            ->with('roleClass', $contributorRoles)
            ->with('contributors', $contributors)
            ->with('file_types', File::getTypes());
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(Project $project)
    {
        return view('projects.edit')
            ->with('project', $project);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(EditProject $request, Project $project)
    {
        if($request->hasFile('icon') && $request->file('icon')->isValid()) {
            if($project->icon != Project::DEFAULT_ICON)
                Storage::delete('public/' . basename($project->icon));
            $path = $request->icon->store('public');
            $project->icon = basename($path, '.png');
        }

        $project->name = $request->input('name');
        $project->url = $request->input('url');
        $project->bugtracker_url = $request->input('bugtracker_url');
        $project->prerelease_url = $request->input('prerelease_url');
        $project->release_date = $request->input('release_date');
        $project->save();

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        //
    }
    
    public function export(Project $project, $status = 'all')
    {
        if(!$project->allFilePathsAreUnique()) {
            return redirect()->route('projects.show', [$project->id])
                ->with('error',
                    'There are duplicate file paths. Cannot generate an archive.');
        }
        $generatedFiles = [];
        $languages = [];
        if($status == 'complete') {
            $languages = $project->completeLanguages()->get();
            if(empty($languages))
                return redirect()->back();
        }
        foreach($project->files()->get() as $file) {
            if($status == 'all') {
                $languages = Language::whereIn('id',
                    Translation::whereIn('text_id', $file->texts()->select('id')->getQuery())
                        ->distinct()->select('language_id')->getQuery())->get();
                if(empty($languages))
                    continue;
            }
            foreach($languages as $lang) {
                $file_key = $file->path . '.' . $file->getFileInstance()->getExtension();
                $generatedFiles[$file_key][$lang->iso_code] = $file->export($lang);
            }
        }
        if(empty($generatedFiles))
            return redirect()->back();

        $filename = $project->name . '.zip';
        $headers = [
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Content-type'        => 'application/zip',
            'Content-Disposition' => 'attachment; filename=' . $filename,
            'Expires'             => '0',
            'Pragma'              => 'public',
        ];
        $tmpfile = tempnam(storage_path('app'), 'zip');
        $zip = new ZipArchive();
        $zip->open($tmpfile, ZipArchive::CREATE);
        foreach($generatedFiles as $path => $languages) {
            foreach($languages as $lang => $generatedFilePath) {
                $inArchivePath = str_replace('%lang%', $lang, $path);
                $zip->addFile(storage_path('app/' . $generatedFilePath), $inArchivePath);
            }
        }
        $zip->close();

        return response()->download($tmpfile, $filename, $headers)->deleteFileAfterSend(true);
    }
}
