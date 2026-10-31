<?php

namespace App\Filament\Resources\Dishes\Schemas;

use App\Enums\Aisle;
use App\Enums\Allergen;
use App\Enums\DishStatus;
use App\Enums\Meal;
use App\Enums\Mood;
use App\Models\Dish;
use App\Models\Ingredient;
use App\Planning\Nutrition;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DishForm
{
    public const EFFORT_LEVELS = [1 => 'Easy', 2 => 'Medium', 3 => 'Involved'];

    public const SPICE_LEVELS = [0 => 'Mild', 1 => 'Medium', 2 => 'Spicy'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                self::basics(),
                self::nutrition(),
                self::ingredients(),
                self::steps(),
                Section::make('Used in plans')
                    ->schema([
                        Text::make(fn (?Dish $record) => $record?->planItems()->with('planTemplate')->get()
                            ->pluck('planTemplate.name')->unique()->join(', ').'. A dish used in a plan cannot be deleted; hide it instead.'),
                    ])
                    ->visible(fn (?Dish $record) => $record?->isUsedInPlans() ?? false),
            ]);
    }

    private static function basics(): Section
    {
        return Section::make('Basics')->schema([
            TextInput::make('name_en')->label('Name in English')->placeholder('e.g. Methi malai paneer')->required()->maxLength(255),
            TextInput::make('name_hi')->label('Name in Hindi (optional)')->placeholder('e.g. मेथी मलाई पनीर')->maxLength(255),
            ToggleButtons::make('status')
                ->options(DishStatus::class)
                ->default(DishStatus::Draft)
                ->inline()
                ->required()
                ->live()
                ->helperText(fn (Get $get) => self::status($get('status'))?->explanation()),
            CheckboxList::make('countries')
                ->relationship('countries', 'name', fn ($query) => $query->orderBy('position'))
                ->columns(3)
                ->required()
                ->validationMessages(['required' => 'Pick at least one country.']),
            CheckboxList::make('meals')
                ->options(Meal::class)
                ->columns(3)
                ->required()
                ->validationMessages(['required' => 'Pick at least one meal.']),
            ToggleButtons::make('mood')->options(Mood::class)->default(Mood::Wholesome)->inline()->required(),
            Grid::make(['default' => 1, 'sm' => 2])->schema([
                Select::make('cuisine_id')->label('Cuisine')->relationship('cuisine', 'name', fn ($query) => $query->orderBy('position'))->required(),
                Select::make('effort')->options(self::EFFORT_LEVELS)->default(1)->required()->selectablePlaceholder(false),
                TextInput::make('prep_min')->label('Prep time (min)')->integer()->minValue(0)->default(10)->required(),
                TextInput::make('cook_min')->label('Cook time (min)')->integer()->minValue(0)->default(20)->required(),
                Select::make('spice')->label('Spice level')->options(self::SPICE_LEVELS)->default(1)->required()->selectablePlaceholder(false),
            ]),
        ]);
    }

    private static function nutrition(): Section
    {
        $number = fn (string $name, string $label) => TextInput::make($name)->label($label)->integer()->minValue(0)->required()->live(onBlur: true);

        return Section::make('Nutrition per serving')->schema([
            Grid::make(['default' => 2, 'md' => 5])->schema([
                $number('kcal', 'Calories (kcal)')->minValue(1),
                $number('protein_g', 'Protein (g)'),
                $number('carbs_g', 'Carbs (g)'),
                $number('fat_g', 'Fat (g)'),
                $number('fibre_g', 'Fibre (g)'),
            ]),
            Callout::make(fn (Get $get) => self::calorieCheck(self::nutritionFrom($get)))
                ->color(fn (Get $get) => self::nutritionFrom($get)->kcalDisagreesWithMacros() ? 'warning' : 'gray'),
        ]);
    }

    private static function ingredients(): Section
    {
        return Section::make('Ingredients per serving')->schema([
            Repeater::make('dishIngredients')
                ->hiddenLabel()
                ->relationship()
                ->orderColumn('position')
                ->table([
                    Repeater\TableColumn::make('Ingredient'),
                    Repeater\TableColumn::make('Quantity')->width('12rem'),
                ])
                ->schema([
                    Select::make('ingredient_id')
                        ->hiddenLabel()
                        ->options(self::ingredientOptions())
                        ->searchable()
                        ->required()
                        ->distinct()
                        ->live(),
                    TextInput::make('qty_per_serving')
                        ->hiddenLabel()
                        ->numeric()
                        ->minValue(0.01)
                        ->required()
                        ->suffix(fn (Get $get) => $get('ingredient_id') ? Ingredient::find((int) $get('ingredient_id'))?->unit : null),
                ])
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel('Add ingredient')
                ->live(),
            Text::make(fn (Get $get) => self::derivedSummary($get)),
        ]);
    }

    private static function steps(): Section
    {
        return Section::make('Steps')->schema([
            Repeater::make('steps_en')
                ->hiddenLabel()
                ->simple(Textarea::make('step')->rows(2)->required())
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel('Add step'),
        ]);
    }

    private static function status(mixed $state): ?DishStatus
    {
        return $state instanceof DishStatus ? $state : DishStatus::tryFrom((string) $state);
    }

    /**
     * @return array<string, array<int, string>> Ingredients grouped by aisle, by name within each.
     */
    private static function ingredientOptions(): array
    {
        $options = [];
        foreach (Ingredient::orderBy('name_en')->get() as $ingredient) {
            $options[$ingredient->aisle->getLabel()][$ingredient->id] = $ingredient->name_en;
        }

        return collect(Aisle::cases())
            ->mapWithKeys(fn (Aisle $aisle) => [$aisle->getLabel() => $options[$aisle->getLabel()] ?? []])
            ->filter()
            ->all();
    }

    private static function nutritionFrom(Get $get): Nutrition
    {
        return new Nutrition((int) $get('kcal'), (int) $get('protein_g'), (int) $get('carbs_g'), (int) $get('fat_g'), (int) $get('fibre_g'));
    }

    public static function calorieCheck(Nutrition $nutrition): string
    {
        if ($nutrition->kcal <= 0) {
            return 'Enter calories to check them against the macros.';
        }

        $estimate = $nutrition->kcalFromMacros();

        return $nutrition->kcalDisagreesWithMacros()
            ? "Protein, carbs and fat add up to about {$estimate} kcal, but {$nutrition->kcal} kcal is entered. Check the numbers."
            : "Protein, carbs and fat add up to about {$estimate} kcal, which matches the {$nutrition->kcal} kcal entered.";
    }

    /**
     * The diet label and allergens worked out live from the ingredients being edited.
     */
    private static function derivedSummary(Get $get): string
    {
        /** @var array<array-key, array{ingredient_id?: mixed}> $lines */
        $lines = $get('dishIngredients') ?? [];
        $ids = collect($lines)->pluck('ingredient_id')->filter()->map(fn ($id) => (int) $id)->values();
        $byId = Ingredient::findMany($ids)->keyBy('id');
        $ingredients = $ids->map(fn (int $id) => $byId->get($id))->filter()->values();

        $diet = Dish::dietOf($ingredients);
        $allergens = Dish::allergensOf($ingredients);
        $minutes = (int) $get('prep_min') + (int) $get('cook_min');

        return $diet->getLabel().', worked out from the ingredients. '
            .($allergens->isEmpty() ? 'No tracked allergens.' : 'Contains '.$allergens->map(fn (Allergen $a) => $a->getLabel())->join(', ').'.')
            ." Total time {$minutes} min.";
    }
}
