<?php

namespace App\Http\Requests;

use App\File;
use App\Language;
use Illuminate\Foundation\Http\FormRequest;

class ImportTranslation extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $file = File::find($this->route('file')['id']);
        $lang = Language::find($this->route('lang')['id']);

        return $file && $lang && $this->user()->can('translate-file', [$file, $lang]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'catkeys' => 'required|file|max:1024'
        ];
    }
}
