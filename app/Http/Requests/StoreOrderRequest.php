<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['dine_in', 'takeaway'])],
            'note' => ['nullable', 'string', 'max:255'],
            'table_id' => ['nullable', 'integer', 'exists:dining_tables,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'La commande doit contenir au moins un article.',
            'items.*.qty.min' => 'La quantité doit être d\'au moins 1.',
        ];
    }
}
