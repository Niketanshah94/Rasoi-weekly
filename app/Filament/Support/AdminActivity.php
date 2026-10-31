<?php

namespace App\Filament\Support;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes the admin activity log: one plain-English line per change, with who made it.
 */
final class AdminActivity
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public static function record(string $description, ?Model $subject = null, array $properties = []): void
    {
        $logger = activity('admin')->withProperties($properties);

        $staff = auth('staff')->user();
        if ($staff instanceof Staff) {
            $logger->causedBy($staff);
        }
        if ($subject) {
            $logger->performedOn($subject);
        }

        $logger->log($description);
    }
}
