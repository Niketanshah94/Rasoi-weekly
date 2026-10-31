<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\CustomerContext;
use App\Http\Controllers\Controller;
use App\Http\Presenters\WeekPresenter;
use App\Planning\HouseholdPlanner;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    use CustomerContext;

    public function show(Request $request, HouseholdPlanner $planner): Response
    {
        [$user, $household] = $this->customer($request);

        return Inertia::render('app/Account', [
            'shell' => WeekPresenter::shell($household, $user->name),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'country' => $household->country->name,
                'viaGoogle' => $user->google_id !== null,
            ],
            'household' => WeekPresenter::household($household),
            'planName' => $planner->planFor($household)->name,
        ]);
    }
}
