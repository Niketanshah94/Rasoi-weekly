<?php

namespace App\Policies;

use App\Models\Ingredient;
use App\Models\Staff;

class IngredientPolicy
{
    public function viewAny(Staff $staff): bool
    {
        return true;
    }

    public function view(Staff $staff, Ingredient $ingredient): bool
    {
        return true;
    }

    public function create(Staff $staff): bool
    {
        return $staff->role->canEditContent();
    }

    public function update(Staff $staff, Ingredient $ingredient): bool
    {
        return $staff->role->canEditContent();
    }

    /**
     * An ingredient in any recipe stays, so no dish loses part of its recipe.
     */
    public function delete(Staff $staff, Ingredient $ingredient): bool
    {
        return $staff->role->canEditContent() && ! $ingredient->dishIngredients()->exists();
    }

    public function deleteAny(Staff $staff): bool
    {
        return false;
    }
}
