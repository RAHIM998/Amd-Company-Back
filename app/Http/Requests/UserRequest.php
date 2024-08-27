<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'name' => ['required', 'min:2', 'regex:/^[\pL\s]+$/u'],
            'telephone' => ['required', 'regex:/^(\+\d{1,4})?\d+$/'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => [Rule::in(['admin', 'user'])],
            'image' => 'nullable|image',
            'password' => ['required', 'min:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire !!',
            'name.min' => 'Le nom doit contenir au moins 2 caractères !!',
            'name.regex' => 'Le nom ne peut contenir que des lettres et des espaces !!',
            'telephone.required' => 'Le téléphone est obligatoire !!',
            'telephone.regex' => 'Le numéro de téléphone doit contenir uniquement des chiffres et éventuellement un signe + au début !!',
            'email.required' => 'L\'email est obligatoire !!',
            'email.email' => 'L\'email doit être une adresse valide !!',
            'email.unique' => 'Cet email est déjà utilisé. Veuillez vous connecter !!',
            'role.required' => 'Le rôle est obligatoire !!',
            'role.in' => 'Le rôle sélectionné n\'est pas valide !!',
            'password.required' => 'Le mot de passe est obligatoire !!',
            'password.min' => 'Pour des raisons de sécurité, le mot de passe doit contenir au moins 4 caractères !!',
        ];
    }
}
