<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Models\Staff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Every change made in the admin panel, newest first. Read only.
 */
class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $navigationLabel = 'Activity';

    protected static ?string $modelLabel = 'activity';

    protected static ?string $pluralModelLabel = 'activity';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return auth('staff')->user() instanceof Staff;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('log_name', 'admin')->with('causer'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('description')->label('Change')->wrap()->searchable(),
                TextColumn::make('causer.email')->label('By')->placeholder('System'),
                TextColumn::make('created_at')->label('When')->dateTime('j M Y, H:i')->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
        ];
    }
}
