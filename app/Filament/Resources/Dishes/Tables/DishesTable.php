<?php

namespace App\Filament\Resources\Dishes\Tables;

use App\Enums\Diet;
use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Models\Dish;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DishesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['ingredients', 'countries']))
            ->defaultSort('name_en')
            ->columns([
                TextColumn::make('name_en')
                    ->label('Dish')
                    ->searchable(['name_en', 'name_hi'])
                    ->sortable()
                    ->description(fn (Dish $record) => $record->countries->pluck('code')->join(', ').'. '
                        .$record->meals->map(fn (Meal $meal) => $meal->getLabel())->join(', ').'.'),
                TextColumn::make('diet')
                    ->state(fn (Dish $record): Diet => $record->diet())
                    ->badge(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('kcal')->label('kcal')->numeric()->sortable(),
                TextColumn::make('protein_g')->label('Protein (g)')->numeric()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(DishStatus::class),
                SelectFilter::make('meal')
                    ->options(Meal::class)
                    ->query(fn (Builder $query, array $data) => filled($data['value'])
                        ? $query->whereJsonContains('meals', $data['value'])
                        : $query),
                SelectFilter::make('country')
                    ->relationship('countries', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
