<?php

namespace App\Http\Requests\Household;

use Illuminate\Contracts\Validation\ValidationRule;

class OnboardingRequest extends HouseholdRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            ...parent::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Add your name to continue.',
            ...parent::messages(),
        ];
    }
}
