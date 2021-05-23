<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditGlossaryEntry extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('update', [$this->entry, $this->glossary]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'text' => ['required', 'max:1000',
                Rule::unique('glossary')
                    ->where('language_id', $this->glossary->id)
            ],
            'translation' => 'required|max:1000'
        ];
    }
}
