<?php

namespace App\Filament\Resources\Dishes\Pages;

use App\Filament\Resources\Dishes\DishResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDishes extends ListRecords
{
    protected static string $resource = DishResource::class;

    protected ?string $subheading = 'Changes go live in the app as soon as you save.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New dish'),
        ];
    }
}
