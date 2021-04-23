<?php

namespace App\Http\Requests;

use App\File;
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
        $file = File::find($this->route('file')['id']);

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
            'catkeys' => 'required|file|max:1024'
        ];
    }
}
