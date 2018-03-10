<?php

namespace Polyglot\Http\Requests;

use Polyglot\File;
use Illuminate\Foundation\Http\FormRequest;

class UploadFile extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $file = File::find($this->route('file'))->first();

        return $file && $this->user()->can('modify-file', $file);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'catkeys' => 'required|file|mimetypes:text/plain|max:1024'
        ];
    }
}
