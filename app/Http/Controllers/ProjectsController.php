<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\Project;
use Polyglot\ProjectUser;
use Polyglot\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

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
        $projects = Project::all();
        return view('projects.index')->with('projects', $projects);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $project = new Project;
        $project->name = $request->input('name');
        $project->url = '';
        $project->save();

        $project->users()->attach(Auth::id(), ['role' => 2]);

        return \Redirect::route('projects.index')->with('message', 'Project added.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \Polyglot\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, $display = 'active')
    {
        // count progress
        $languages = Language::orderBy('iso_code')->get();
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
                            'needs_work' => round($item->needs_work / $count * 100),
                            'translated' => round($item->translated / $count * 100)
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
            ->get()->groupBy('user_id')->sortBy(function($c) {
                return strtolower($c[0]->user->name);
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
            ->with('contributors', $contributors);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Polyglot\Project  $project
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
     * @param  \Polyglot\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project)
    {
        $project->name = $request->input('name');
        $project->save();

        return \Redirect::route('projects.show', [$project->id])
            ->with('message', 'Project saved.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \Polyglot\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        //
    }
}
