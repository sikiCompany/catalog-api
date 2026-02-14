<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'sometimes',
                'string',
                'max:50',
                Rule::unique('products')->ignore($this->product)
            ],
            'name' => 'sometimes|string|min:3|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'sometimes|numeric|min:0.01|max:999999.99',
            'category' => 'sometimes|string|max:100',
            'status' => 'sometimes|in:active,inactive'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'Este SKU já está em uso',
            'name.min' => 'O nome deve ter no mínimo 3 caracteres',
            'price.min' => 'O preço deve ser maior que zero'
        ];
    }
}