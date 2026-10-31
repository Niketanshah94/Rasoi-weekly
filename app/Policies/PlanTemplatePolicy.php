<?php

namespace App\Policies;

use App\Models\PlanTemplate;
use App\Models\Staff;

/**
 * The set of plans is fixed; staff edit their names and meals.
 */
class PlanTemplatePolicy
{
    public function viewAny(Staff $staff): bool
    {
        return true;
    }

    public function view(Staff $staff, PlanTemplate $plan): bool
    {
        return true;
    }

    public function create(Staff $staff): bool
    {
        return false;
    }

    public function update(Staff $staff, PlanTemplate $plan): bool
    {
        return $staff->role->canEditContent();
    }

    public function delete(Staff $staff, PlanTemplate $plan): bool
    {
        return false;
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
