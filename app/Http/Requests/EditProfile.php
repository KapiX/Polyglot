<?php

namespace Polyglot\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditProfile extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $user = $this->user()->id;
        return [
            'name' => 'required|unique:users,name,' . $user . '|max:255',
            'email' => 'required|email|unique:users,email,' . $user . '|max:255'
        ];
    }
}
