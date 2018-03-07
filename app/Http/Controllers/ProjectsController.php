<?php

namespace Polyglot\Http\Controllers;

use Polyglot\Language;
use Polyglot\Project;
use Polyglot\ProjectUser;
use Polyglot\Text;
use Polyglot\Translation;
use Polyglot\Http\Requests\AddProject;
use Polyglot\Http\Requests\EditProject;
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
            $project_texts = Text::select('project_id', 'texts.id as text_id')->join('files', 'file_id', '=', 'files.id')->toSql();
            $project_needs_work = DB::table(DB::raw('(
                    select `project_id`, `language_id`, `all_count` > `translated` as `needs_work` from (
                        select `project_id`, `language_id`, count(id) as `translated` from `translations` left join
                        (' . $project_texts . ') as `project_text` on `project_text`.`text_id` = `translations`.`text_id` and `needs_work` = 0 group by `project_id`, `language_id`
                    ) as `translation_counts` left join (
                        select `project_id` as `id`, count(`text_id`) as `all_count` from (' . $project_texts . ') as `all_texts` group by `id`
                    ) as `texts_count` on `texts_count`.`id` = `translation_counts`.`project_id`
                ) as `project_needs_work`'))
                ->select('project_id')
                ->selectRaw('sum(`needs_work`) > 0 as `needs_work`')
                ->whereNotNull('project_id')
                ->whereIn('language_id', $preferred_languages)
                ->groupBy('project_id')
                ->pluck('needs_work', 'project_id')
                ->union($projects->pluck('id')->mapWithKeys(function($item) { return [$item => 1]; }));
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
     * @param  \Polyglot\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project, $display = 'active')
    {
        $languages = Language::orderBy('iso_code')->get()->keyBy('id');
        // pull the preferred languages to the top
        $preferred_languages = Auth::user()->preferred_languages ?? [];
        $prepend = [];
        foreach($preferred_languages as $id) {
            $prepend[] = $languages->pull($id);
        }
        $prepend = collect($prepend)->sortBy('iso_code')->keyBy('id');
        $languages = $languages->prepend($prepend)->flatten();

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
     * @param  \Polyglot\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        //
    }
}
