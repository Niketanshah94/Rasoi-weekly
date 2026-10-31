<?php

namespace App\Http\Controllers\Household;

use App\Enums\HouseholdRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Household\OnboardingRequest;
use App\Models\Country;
use App\Models\Household;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The six onboarding steps (spec §2): name, who cooks, who's eating, leave-outs, plan style
 * and, when a cook is involved, the cook's details. Saved together at the end.
 */
class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $user = $request->user('web');
        if ($user?->household()) {
            return to_route('dashboard');
        }

        $country = $user->country ?? Country::where('code', Country::INDIA)->firstOrFail();

        return Inertia::render('onboarding/Onboarding', [
            'name' => $user?->name,
            'household' => HouseholdOptions::form(null, $country),
            // Partial reloads pass the answers so far, so plan previews reflect diet and leave-outs.
            'countries' => fn () => HouseholdOptions::countries(HouseholdOptions::dinersFrom($request, $country)),
        ]);
    }

    public function store(OnboardingRequest $request): RedirectResponse
    {
        $user = $request->user('web');
        abort_unless($user !== null, 403);

        if ($user->household()) {
            return to_route('dashboard');
        }

        DB::transaction(function () use ($request, $user) {
            $user->update(['name' => $request->validated('name')]);
            Household::create($request->householdAttributes())->addMember($user, HouseholdRole::Owner);
        });

        // The free-trial offer follows here once billing exists (M7).
        return to_route('dashboard');
    }
}
