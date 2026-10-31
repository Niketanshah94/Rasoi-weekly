<?php

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Filament\Resources\Ingredients\Schemas\IngredientForm;
use App\Filament\Support\AdminActivity;
use App\Models\Ingredient;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Ingredient $record
 */
class CreateIngredient extends CreateRecord
{
    protected static string $resource = IngredientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return IngredientForm::withoutBlankLocalNames($data);
    }

    protected function afterCreate(): void
    {
        AdminActivity::record("Created the ingredient “{$this->record->name_en}”.", $this->record);
    }
}
