<?php

namespace App\Http\Requests\Household;

use App\Enums\LeaveOut;
use App\Enums\MenuLanguage;
use App\Enums\WhoCooks;
use App\Models\Country;
use App\Models\PlanTemplate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The household fields shared by onboarding and Household settings (spec §2, §3).
 */
class HouseholdRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            // People type phone numbers with spaces, as the placeholder shows them.
            'cook_phone' => filled($this->input('cook_phone')) ? preg_replace('/[\s\-()]/', '', (string) $this->input('cook_phone')) : null,
            'leave_out' => $this->input('leave_out', []),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $country = $this->country();

        return [
            'country' => ['required', Rule::exists(Country::class, 'code')],
            'who_cooks' => ['required', Rule::enum(WhoCooks::class)],
            'people' => ['required', 'integer', 'between:1,12'],
            'nonveg_eaters' => ['required', 'integer', 'min:0', 'lte:people'],
            'veg_eat_eggs' => ['required', 'boolean'],
            'leave_out' => ['present', 'array'],
            'leave_out.*' => ['distinct', Rule::enum(LeaveOut::class)->only($country ? LeaveOut::offeredIn($country) : [])],
            'plan' => ['required', Rule::in($country ? PlanTemplate::offeredIn($country)->pluck('key')->all() : [])],
            'cook_name' => ['nullable', 'string', 'max:60'],
            'cook_phone' => ['nullable', 'string', 'max:20', $this->mobileNumberRule($country)],
            'cook_language' => ['required', Rule::enum(MenuLanguage::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nonveg_eaters.lte' => 'That’s more people than are eating.',
            'leave_out.*.enum' => 'That isn’t something we can leave out here.',
            'plan.in' => 'Pick one of the plans for your country.',
        ];
    }

    public function country(): ?Country
    {
        return Country::where('code', $this->input('country'))->first();
    }

    /**
     * The validated fields as household attributes. Cook details are dropped when nobody else cooks.
     *
     * @return array<string, mixed>
     */
    public function householdAttributes(): array
    {
        $data = $this->validated();
        $country = $this->country();
        $involvesCook = WhoCooks::from($data['who_cooks'])->involvesCook();

        return [
            'country_id' => $country?->id,
            'who_cooks' => $data['who_cooks'],
            'people' => (int) $data['people'],
            'nonveg_eaters' => (int) $data['nonveg_eaters'],
            'veg_eat_eggs' => (bool) $data['veg_eat_eggs'],
            'leave_out' => array_values($data['leave_out']),
            'plan_template_id' => PlanTemplate::where('key', $data['plan'])->value('id'),
            'cook_name' => $involvesCook ? ($data['cook_name'] ?? null) : null,
            'cook_phone' => $involvesCook ? ($data['cook_phone'] ?? null) : null,
            'cook_language' => $data['cook_language'],
        ];
    }

    /**
     * The cook's number must fit the country's mobile pattern (countries.json).
     */
    private function mobileNumberRule(?Country $country): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($country) {
            if ($country && ! preg_match('/'.str_replace('/', '\/', $country->mobile_regex).'/', (string) $value)) {
                $fail($country->mobile_error);
            }
        };
    }
}
