<?php

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Filament\Resources\Ingredients\Schemas\IngredientForm;
use App\Filament\Support\AdminActivity;
use App\Models\Ingredient;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Ingredient $record
 */
class EditIngredient extends EditRecord
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(fn (Ingredient $record) => AdminActivity::record("Deleted the ingredient “{$record->name_en}”.", $record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return IngredientForm::withoutBlankLocalNames($data);
    }

    protected function afterSave(): void
    {
        AdminActivity::record(
            "Updated the ingredient “{$this->record->name_en}”.",
            $this->record,
            ['changed' => array_keys($this->record->getChanges())],
        );
    }
}
