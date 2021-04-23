<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\File;

class EditFile extends FormRequest
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
            'name' => 'required|max:255',
            'path' => 'required|max:255|regex:/^[^\\\\]*%lang%[^\\\\]*$/'
        ];
    }

    public function messages()
    {
        return [
            'path.regex' => 'Path must contain "%lang%" and must not contain backslashes (\).'
        ];
    }
}
