<?php

namespace App\Filament\Resources\PlanTemplates\Tables;

use App\Enums\DayHealth;
use App\Models\PlanTemplate;
use App\Planning\HealthyDay;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlanTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['items.dish', 'countries']))
            ->defaultSort('position')
            ->paginated(false)
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->description(fn (PlanTemplate $record) => $record->description),
                TextColumn::make('countries.name')->label('Offered in')->listWithLineBreaks(),
                TextColumn::make('healthy_days')
                    ->label('Healthy days')
                    ->state(fn (PlanTemplate $record) => self::healthyDays($record).' of 7 healthy')
                    ->badge()
                    ->color(fn (PlanTemplate $record) => match (true) {
                        self::healthyDays($record) >= 5 => 'success',
                        self::healthyDays($record) >= 3 => 'warning',
                        default => 'danger',
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    private static function healthyDays(PlanTemplate $plan): int
    {
        return collect($plan->dailyNutrition())
            ->filter(fn ($day) => HealthyDay::status($day) === DayHealth::Healthy)
            ->count();
    }
}
