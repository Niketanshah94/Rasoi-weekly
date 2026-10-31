<?php

namespace App\Filament\Resources\PlanTemplates\Schemas;

use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Enums\Weekday;
use App\Models\Dish;
use App\Models\PlanTemplate;
use App\Planning\HealthCheck;
use App\Planning\HealthyDay;
use App\Planning\Nutrition;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PlanTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Plan')->schema([
                    TextInput::make('name')->label('Plan name')->required()->maxLength(255),
                    TextInput::make('description')->label('Short description')->required()->maxLength(255),
                ]),
                Section::make('Meals by day')
                    ->description('Pick from live dishes for each meal. The day’s healthy-day check updates as you go.')
                    ->schema([
                        Tabs::make('Days')->tabs(array_map(self::dayTab(...), Weekday::cases())),
                    ]),
            ]);
    }

    private static function dayTab(Weekday $day): Tab
    {
        $selects = array_map(fn (Meal $meal) => Select::make("week.{$meal->value}.{$day->value}")
            ->label($meal->getLabel())
            ->options(fn (?PlanTemplate $record) => $record ? self::options($record, $meal) : [])
            ->searchable()
            ->required()
            ->live()
            ->helperText(fn (mixed $state) => self::dishSummary($state)), Meal::cases());

        return Tab::make($day->shortLabel())
            ->badge(fn (Get $get) => HealthyDay::targetsMet(self::dayNutrition($get, $day)).'/5')
            ->badgeColor(fn (Get $get) => HealthyDay::status(self::dayNutrition($get, $day))->color())
            ->schema([
                ...$selects,
                Callout::make(fn (Get $get) => $day->getLabel().': '.HealthyDay::status(self::dayNutrition($get, $day))->label())
                    ->description(fn (Get $get) => collect(HealthyDay::checks(self::dayNutrition($get, $day)))
                        ->map(fn (HealthCheck $check) => "{$check->label}: {$check->value} (target {$check->target}), {$check->state->label()}.")
                        ->join(' '))
                    ->color(fn (Get $get) => HealthyDay::status(self::dayNutrition($get, $day))->color()),
            ]);
    }

    /**
     * Live dishes for the meal, plus the current pick if it is no longer live.
     *
     * @return array<int, string>
     */
    private static function options(PlanTemplate $record, Meal $meal): array
    {
        $options = $record->dishPool($meal)->pluck('name_en', 'id')->all();

        $current = $record->items->filter(fn ($item) => $item->meal === $meal)->pluck('dish_id')->diff(array_keys($options));
        foreach (Dish::findMany($current) as $dish) {
            $options[$dish->id] = $dish->name_en.($dish->status === DishStatus::Published ? '' : ' (not live)');
        }

        return $options;
    }

    private static function dishSummary(mixed $dishId): ?string
    {
        $dish = filled($dishId) ? Dish::with('ingredients')->find((int) $dishId) : null;

        return $dish ? "{$dish->diet()->getLabel()}, {$dish->kcal} kcal, {$dish->protein_g} g protein, ".strtolower($dish->mood->getLabel()).'.' : null;
    }

    private static function dayNutrition(Get $get, Weekday $day): Nutrition
    {
        $ids = collect(Meal::cases())
            ->map(fn (Meal $meal) => $get("week.{$meal->value}.{$day->value}"))
            ->filter()
            ->map(fn ($id) => (int) $id);
        $dishes = Dish::findMany($ids)->keyBy('id');

        // A dish picked for two meals counts twice.
        return Nutrition::sum($ids->map(fn (int $id) => $dishes->get($id))->filter());
    }
}
