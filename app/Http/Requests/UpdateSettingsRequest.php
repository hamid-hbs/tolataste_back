<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurantName' => ['sometimes', 'string', 'max:120'],
            'restaurantAddress' => ['sometimes', 'string', 'max:255'],
            'restaurantPhone' => ['sometimes', 'string', 'max:40'],
            'restaurantEmail' => ['sometimes', 'email', 'max:180'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'taxRate' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'defaultTableCount' => ['sometimes', 'integer', 'min:0'],
            'paymentMethods' => ['sometimes', 'array'],
            'autoPrintKitchen' => ['sometimes', 'boolean'],
            'autoPrintBill' => ['sometimes', 'boolean'],
        ];
    }
}
