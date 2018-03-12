<?php

namespace Polyglot\Http\Requests;

use Polyglot\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class EditProject extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $project = Project::find($this->route('project')['id']);

        return $project && $this->user()->can('modify-project', $project);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $uniqueName = 'unique:projects';
        $project = Project::find($this->route('project'))->first();
        if($project)
            $uniqueName .= ',name,' . $project->id;
        return [
            'name' => 'required|' . $uniqueName . '|max:255',
            'url' => 'nullable|url|max:255',
            'bugtracker_url' => 'nullable|url|max:255',
            'prerelease_url' => 'nullable|url|max:255',
            'icon' => 'nullable|file|image|mimes:png|max:16|dimensions:width=32,height=32',
            'release_date' => 'nullable|date'
        ];
    }
}
