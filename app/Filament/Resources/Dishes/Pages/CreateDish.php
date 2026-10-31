<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Resources\Dishes\DishResource;
use App\Filament\Support\AdminActivity;
use App\Models\Dish;
use Filament\Resources\Pages\CreateRecord;

/**
 * @property Dish $record
 */
class CreateDish extends CreateRecord
{
    protected static string $resource = DishResource::class;

    protected function afterCreate(): void
    {
        AdminActivity::record(
            "Created the dish “{$this->record->name_en}” (".strtolower($this->record->status->getLabel()).').',
            $this->record,
        );
    }
}
