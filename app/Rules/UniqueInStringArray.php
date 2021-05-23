<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Validation\Rules\DatabaseRule;

use Illuminate\Support\Facades\DB;

class UniqueInStringArray implements Rule
{
    use DatabaseRule;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $column = $this->column == 'NULL' ? $attribute : $this->column;
        $placeholders = array_fill(0, count($value), '?');
        $result = DB::table($this->table)
            ->whereRaw('BINARY ' . $column . ' IN ('
                . implode(',', $placeholders) . ')', $value);
        foreach($this->wheres as $where) {
            $result->where($where['column'], $where['value']);
        }
        foreach($this->using as $func) {
            $result = $func($result);
        }
        return $result->count() == 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute field contains items that already exist.';
    }
}
