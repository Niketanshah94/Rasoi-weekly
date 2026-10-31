<?php

namespace App\Filament\Resources\PlanTemplates\Pages\Concerns;

use App\Models\PlanTemplate;
use App\Models\PlanTemplateItem;

/**
 * The form edits the week as week.{meal}.{day} => dish id.
 *
 * @property PlanTemplate $record
 */
trait FillsWeek
{
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['week'] = [];
        foreach ($this->record->items as $item) {
            /** @var PlanTemplateItem $item */
            $data['week'][$item->meal->value][$item->weekday->value] = $item->dish_id;
        }

        return $data;
    }
}
