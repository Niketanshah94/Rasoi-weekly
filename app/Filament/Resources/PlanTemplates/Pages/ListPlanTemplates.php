<?php

namespace App\Filament\Resources\PlanTemplates\Pages;

use App\Filament\Resources\PlanTemplates\PlanTemplateResource;
use Filament\Resources\Pages\ListRecords;

class ListPlanTemplates extends ListRecords
{
    protected static string $resource = PlanTemplateResource::class;

    protected ?string $subheading = 'The templates every household starts from. The app adjusts them for each household’s diet and leave-outs.';
}
