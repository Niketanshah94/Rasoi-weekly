<?php

namespace App\Filament\Resources\PlanTemplates\Pages;

use App\Filament\Resources\PlanTemplates\Pages\Concerns\FillsWeek;
use App\Filament\Resources\PlanTemplates\PlanTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPlanTemplate extends ViewRecord
{
    use FillsWeek;

    protected static string $resource = PlanTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
