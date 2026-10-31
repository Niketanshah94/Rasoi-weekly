<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Resources\Dishes\DishResource;
use App\Filament\Support\AdminActivity;
use App\Models\Dish;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property Dish $record
 */
class EditDish extends EditRecord
{
    protected static string $resource = DishResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->after(fn (Dish $record) => AdminActivity::record("Deleted the dish “{$record->name_en}”.", $record)),
        ];
    }

    protected function afterSave(): void
    {
        AdminActivity::record(
            "Updated the dish “{$this->record->name_en}” (".strtolower($this->record->status->getLabel()).').',
            $this->record,
            ['changed' => array_keys($this->record->getChanges())],
        );
    }
}
