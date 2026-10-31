<?php

namespace App\Filament\Resources\Ingredients\Schemas;

use App\Enums\Aisle;
use App\Enums\Allergen;
use App\Models\Ingredient;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class IngredientForm
{
    /**
     * Countries whose shoppers know some ingredients by another name.
     */
    public const LOCAL_NAME_COUNTRIES = ['US' => 'United States', 'GB' => 'United Kingdom', 'AE' => 'United Arab Emirates', 'SG' => 'Singapore'];

    public const UNITS = ['g' => 'g', 'ml' => 'ml', 'pcs' => 'pcs', 'slices' => 'slices', 'packs' => 'packs', 'bunch' => 'bunch', 'cups' => 'cups', 'cans' => 'cans'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Ingredient')
                    ->description('Every dish’s veg or non-veg label and allergens come from this list. Changing the aisle or allergens changes them for every dish that uses it.')
                    ->schema([
                        TextInput::make('name_en')->label('Name in English')->required()->maxLength(255),
                        TextInput::make('name_hi')->label('Name in Hindi (optional)')->maxLength(255),
                        Grid::make(['default' => 1, 'sm' => 2])->schema([
                            Select::make('aisle')->options(Aisle::class)->required()
                                ->helperText('Anything in Meat & fish makes a dish non-veg.'),
                            Select::make('unit')
                                ->label('Sold by')
                                ->options(self::UNITS)
                                ->required()
                                ->disabled(fn (?Ingredient $record) => $record?->dishIngredients()->exists() ?? false)
                                ->helperText(fn (?Ingredient $record) => $record?->dishIngredients()->exists()
                                    ? 'Recipes give their quantities in this unit, so it can’t change while dishes use it.'
                                    : null),
                        ]),
                        CheckboxList::make('allergens')->options(Allergen::class)->columns(3),
                    ]),
                Section::make('Local names')
                    ->description('Shown on the shopping list in these countries instead of the English name. Leave blank to use the English name.')
                    ->schema([
                        Grid::make(['default' => 1, 'sm' => 2])->schema(
                            collect(self::LOCAL_NAME_COUNTRIES)
                                ->map(fn (string $name, string $code) => TextInput::make("local_names.{$code}")->label($name)->maxLength(255))
                                ->values()
                                ->all()
                        ),
                    ]),
            ]);
    }

    /**
     * Blank local names are dropped so the English name is used.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withoutBlankLocalNames(array $data): array
    {
        $names = is_array($data['local_names'] ?? null) ? $data['local_names'] : [];
        $data['local_names'] = array_filter($names, fn ($name) => filled($name));

        return $data;
    }
}
