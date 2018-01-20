<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function show(Project $project)
    {
        $languages = Language::pluck('name', 'id')->all();
        return view('projects.show')->with('project', $project)->with('languages', $languages);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Polyglot\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function edit(Project $project)
    {
        //
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
        if(count($request->get('languages')) > 0) {
            $project->languages()->sync($request->get('languages'));
        }

        return \Redirect::route('projects.show', [$project->id])->with('message', 'Languages saved.');
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
