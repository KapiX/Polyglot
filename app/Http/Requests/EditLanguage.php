<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Language;

class EditLanguage extends FormRequest
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
        $uniqueIsoCode = 'unique:languages,iso_code';
        $uniqueName = 'unique:languages,name';
        $language = Language::find($this->route('language')['id']);
        if($language) {
            $uniqueIsoCode .= ',' . $language->id;
            $uniqueName .= ',' . $language->id;
        }
        return [
            'iso_code' => 'required|' . $uniqueIsoCode . '|max:20',
            'name' => 'required|' . $uniqueName . '|max:255',
            'style_guide_url' => 'nullable|url|max:255',
            'terminology_url' => 'nullable|url|max:255'
        ];
    }
}
