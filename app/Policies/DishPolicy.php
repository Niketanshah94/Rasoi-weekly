<?php

namespace App\Policies;

use App\Models\Dish;
use App\Models\Staff;

class DishPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return true;
    }

    public function view(Staff $staff, Dish $dish): bool
    {
        return true;
    }

    public function create(Staff $staff): bool
    {
        return $staff->role->canEditContent();
    }

    public function update(Staff $staff, Dish $dish): bool
    {
        return $staff->role->canEditContent();
    }

    /**
     * A dish used in a plan cannot be deleted, only hidden (spec §9).
     */
    public function delete(Staff $staff, Dish $dish): bool
    {
        return $staff->role->canEditContent() && ! $dish->isUsedInPlans();
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
