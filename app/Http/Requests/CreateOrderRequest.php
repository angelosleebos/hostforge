<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer.name' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.phone' => ['nullable', 'string', 'max:50'],
            'customer.address' => ['nullable', 'string', 'max:500'],
            'customer.city' => ['nullable', 'string', 'max:100'],
            'customer.postal_code' => ['nullable', 'string', 'max:20'],
            'customer.country' => ['nullable', 'string', 'max:100'],
            
            'hosting_package_id' => ['required', 'exists:hosting_packages,id'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            
            'domain.name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,}$/i'],
            'domain.register_domain' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'customer.name.required' => 'Naam is verplicht',
            'customer.email.required' => 'E-mailadres is verplicht',
            'customer.email.email' => 'Voer een geldig e-mailadres in',
            'hosting_package_id.required' => 'Selecteer een hosting pakket',
            'hosting_package_id.exists' => 'Geselecteerd pakket bestaat niet',
            'billing_cycle.required' => 'Selecteer een betaalperiode',
            'billing_cycle.in' => 'Ongeldige betaalperiode',
            'domain.name.required' => 'Domeinnaam is verplicht',
            'domain.name.regex' => 'Ongeldige domeinnaam',
            'domain.register_domain.required' => 'Geef aan of het domein geregistreerd moet worden',
        ];
    }
}
