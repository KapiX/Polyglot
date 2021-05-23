<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Models\GlossaryEntry;
use App\Rules\UniqueInStringArray;

class AddGlossaryEntry extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', [GlossaryEntry::class, $this->glossary]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $unique = new UniqueInStringArray('glossary');
        $unique->where('language_id', $this->glossary->id);
        return [
            'text' => ['required', 'array', 'max:500', $unique],
            'text.*' => ['distinct:strict'],
            'translation' => 'required|array|max:500'
        ];
    }

    protected function prepareForValidation()
    {
        $trim = function($i) { return trim($i, "\r"); };
        $this->merge([
            'text' => $this->text == '' ?
                [] : array_map($trim, explode("\n", $this->text)),
            'translation' => $this->translation == '' ?
                [] : array_map($trim, explode("\n", $this->translation))
        ]);
    }
}
