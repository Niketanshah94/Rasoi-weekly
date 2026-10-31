<?php

namespace App\Filament\Resources\Dishes;

use App\Filament\Resources\Dishes\Pages\CreateDish;
use App\Filament\Resources\Dishes\Pages\EditDish;
use App\Filament\Resources\Dishes\Pages\ListDishes;
use App\Filament\Resources\Dishes\Pages\ViewDish;
use App\Filament\Resources\Dishes\Schemas\DishForm;
use App\Filament\Resources\Dishes\Tables\DishesTable;
use App\Models\Dish;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class DishResource extends Resource
{
    protected static ?string $model = Dish::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCake;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name_en';

    public static function form(Schema $schema): Schema
    {
        return DishForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DishesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDishes::route('/'),
            'create' => CreateDish::route('/create'),
            'view' => ViewDish::route('/{record}'),
            'edit' => EditDish::route('/{record}/edit'),
        ];
    }
}
