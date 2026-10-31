<?php

namespace App\Filament\Resources\Ingredients\Tables;

use App\Enums\Aisle;
use App\Enums\Allergen;
use App\Models\Ingredient;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class IngredientsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name_en')
            ->columns([
                TextColumn::make('name_en')
                    ->label('Ingredient')
                    ->searchable(['name_en', 'name_hi'])
                    ->sortable()
                    ->description(fn (Ingredient $record) => $record->name_hi),
                TextColumn::make('aisle')->sortable(),
                TextColumn::make('unit')->label('Sold by'),
                TextColumn::make('allergens')
                    ->state(fn (Ingredient $record) => $record->allergens->map(fn (Allergen $a) => $a->getLabel())->join(', ') ?: '—'),
                TextColumn::make('dish_ingredients_count')
                    ->label('Dishes')
                    ->counts('dishIngredients')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('aisle')->options(Aisle::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
