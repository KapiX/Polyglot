<?php

namespace Polyglot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddLanguage extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('global-settings');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'iso_code' => 'required|unique:languages|max:20',
            'name' => 'required|unique:languages|max:255',
            'style_guide_url' => 'nullable|url|max:255',
            'terminology_url' => 'nullable|url|max:255'
        ];
    }
}
