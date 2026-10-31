<?php

namespace App\Policies;

use App\Models\Country;
use App\Models\Staff;

/**
 * Countries are reference data shipped with the app: staff can read them only.
 */
class CountryPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return true;
    }

    public function view(Staff $staff, Country $country): bool
    {
        return true;
    }

    public function create(Staff $staff): bool
    {
        return false;
    }

    public function update(Staff $staff, Country $country): bool
    {
        return false;
    }

    public function delete(Staff $staff, Country $country): bool
    {
        return false;
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
