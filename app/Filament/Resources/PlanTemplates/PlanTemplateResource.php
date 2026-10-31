<?php

namespace App\Filament\Resources\PlanTemplates;

use App\Filament\Resources\PlanTemplates\Pages\EditPlanTemplate;
use App\Filament\Resources\PlanTemplates\Pages\ListPlanTemplates;
use App\Filament\Resources\PlanTemplates\Pages\ViewPlanTemplate;
use App\Filament\Resources\PlanTemplates\Schemas\PlanTemplateForm;
use App\Filament\Resources\PlanTemplates\Tables\PlanTemplatesTable;
use App\Models\PlanTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PlanTemplateResource extends Resource
{
    protected static ?string $model = PlanTemplate::class;

    protected static ?string $modelLabel = 'weekly plan';

    protected static ?string $slug = 'plans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PlanTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlanTemplates::route('/'),
            'view' => ViewPlanTemplate::route('/{record}'),
            'edit' => EditPlanTemplate::route('/{record}/edit'),
        ];
    }
}
