<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

// use Illuminate\Validation\Rule;

class PortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'max:50'],
            'description' => ['max:1000'],
        ];

        // request()->routeIs('portfolio.store|update')
        // request()->isMethod('post|put')
        // $this->method()

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nom',
            'description' => 'Description',
        ];
    }
}
