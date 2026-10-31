<?php

namespace App\Http\Controllers\Household;

use App\Http\Controllers\Controller;
use App\Http\Requests\Household\HouseholdRequest;
use App\Planning\Diners;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Household settings: the onboarding fields, plus the country (spec §1, M4).
 */
class HouseholdController extends Controller
{
    public function edit(Request $request): Response
    {
        $household = $request->user('web')?->household();
        abort_unless($household !== null, 404);

        return Inertia::render('settings/Household', [
            'household' => HouseholdOptions::form($household, $household->country),
            'countries' => HouseholdOptions::countries(Diners::fromHousehold($household)),
        ]);
    }

    public function update(HouseholdRequest $request): RedirectResponse
    {
        $household = $request->user('web')?->household();
        abort_unless($household !== null, 404);

        $household->update($request->householdAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Household updated.']);

        return to_route('household.edit');
    }
}
