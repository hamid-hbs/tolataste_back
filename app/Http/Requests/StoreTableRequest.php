<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'number' => ['required', 'integer', 'min:1', 'max:65535', 'unique:dining_tables,number'],
            'zone' => ['nullable', 'string', 'max:40'],
        ];
    }
}
