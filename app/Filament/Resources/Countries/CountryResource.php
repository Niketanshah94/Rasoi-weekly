<?php

namespace App\Filament\Resources\Countries;

use App\Filament\Resources\Countries\Pages\ListCountries;
use App\Filament\Resources\Countries\Pages\ViewCountry;
use App\Models\Country;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read only: countries ship with the app.
 */
class CountryResource extends Resource
{
    protected static ?string $model = Country::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Phone numbers')->columns(2)->schema([
                TextEntry::make('dial_code'),
                TextEntry::make('mobile_length')->label('Digits'),
                TextEntry::make('mobile_placeholder')->label('Example'),
                TextEntry::make('mobile_error')->label('Error message'),
            ]),
            Section::make('Billing')->columns(2)->schema([
                TextEntry::make('currency'),
                TextEntry::make('billing_provider')->label('Provider'),
                TextEntry::make('web_payment_methods')->label('Web payment methods')->badge(),
                TextEntry::make('cards_label')->label('Cards'),
                TextEntry::make('tax_note')->label('Tax'),
                TextEntry::make('invoice_name')->label('Receipt name'),
            ]),
            Section::make('Wording')->columns(2)->schema([
                TextEntry::make('units'),
                TextEntry::make('shopping_list_name')->label('Shopping list'),
                TextEntry::make('spices_note')->label('Spices'),
                TextEntry::make('ingredient_names')->label('Ingredient names'),
                TextEntry::make('privacy_law')->label('Privacy law')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')->label('Country'),
                TextColumn::make('currency'),
                TextColumn::make('billing_provider')->label('Billing'),
                TextColumn::make('units'),
                TextColumn::make('shopping_list_name')->label('Shopping list'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCountries::route('/'),
            'view' => ViewCountry::route('/{record}'),
        ];
    }
}
