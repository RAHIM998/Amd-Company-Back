<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProduitRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        return [
            'category_id' => 'required|integer|exists:categories,id',
            'libelle' => 'required|string|max:255|regex:/^[\p{L}0-9\s]+$/u',
            'prix' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
