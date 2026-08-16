<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['sometimes', 'integer', 'min:1', 'max:65535', Rule::unique('dining_tables', 'number')->ignore($this->route('table'))],
            'zone' => ['sometimes', 'string', 'max:40'],
        ];
    }
}
