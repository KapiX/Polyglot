<?php

namespace Polyglot\Http\Requests;

use Polyglot\File;
use Illuminate\Foundation\Http\FormRequest;

class AddFile extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // FIXME: restrict to project?
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:255'
        ];
    }
}
