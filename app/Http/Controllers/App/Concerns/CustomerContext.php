<?php

namespace App\Http\Controllers\App\Concerns;

use App\Enums\Weekday;
use App\Models\Household;
use App\Models\User;
use Illuminate\Http\Request;

trait CustomerContext
{
    /**
     * @return array{0: User, 1: Household}
     */
    protected function customer(Request $request): array
    {
        $user = $request->user('web');
        $household = $user?->household();
        abort_unless($user && $household, 404);

        return [$user, $household->load(['country', 'planTemplate'])];
    }

    protected function day(Request $request, int $default): Weekday
    {
        return Weekday::fromIndex($request->integer('day', $default));
    }
}
