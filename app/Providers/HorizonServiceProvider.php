<?php

namespace App\Providers;

use App\Enums\StaffRole;
use App\Models\Staff;
use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Horizon lives on the admin host and is checked against the staff guard,
        // not the customer one.
        Horizon::auth(fn ($request) => Gate::forUser(auth('staff')->user())->allows('viewHorizon'));

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) {
            return $user instanceof Staff && $user->role === StaffRole::Owner;
        });
    }
}
