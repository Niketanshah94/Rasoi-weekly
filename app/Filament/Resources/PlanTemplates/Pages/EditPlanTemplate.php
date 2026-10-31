<?php

namespace App\Filament\Resources\PlanTemplates\Pages;

use App\Filament\Resources\PlanTemplates\Pages\Concerns\FillsWeek;
use App\Filament\Resources\PlanTemplates\PlanTemplateResource;
use App\Filament\Support\AdminActivity;
use App\Models\PlanTemplate;
use App\Models\PlanTemplateItem;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * @property PlanTemplate $record
 */
class EditPlanTemplate extends EditRecord
{
    use FillsWeek;

    protected static string $resource = PlanTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    /**
     * @param  PlanTemplate  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->update(['name' => $data['name'], 'description' => $data['description']]);
        $week = is_array($data['week'] ?? null) ? $data['week'] : [];

        $changed = [];
        foreach ($record->items as $item) {
            /** @var PlanTemplateItem $item */
            $dishId = (int) ($week[$item->meal->value][$item->weekday->value] ?? $item->dish_id);
            if ($dishId !== $item->dish_id) {
                $item->update(['dish_id' => $dishId]);
                $changed[] = "{$item->weekday->value} {$item->meal->value}";
            }
        }

        AdminActivity::record(
            "Updated the plan “{$record->name}”".($changed ? ': '.count($changed).' '.str('meal')->plural(count($changed)).' changed.' : '.'),
            $record,
            ['changed_meals' => $changed, 'changed' => array_keys($record->getChanges())],
        );

        return $record;
    }
}
