<?php

namespace App\Filament\Resources\Activities;

use App\Filament\Concerns\TranslatesResourceNavigation;
use App\Filament\Resources\Activities\Pages\ListActivities;
use App\Filament\Resources\Activities\Pages\ViewActivity;
use App\Filament\Resources\Activities\Schemas\ActivityForm;
use App\Filament\Resources\Activities\Schemas\ActivityInfolist;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityResource extends Resource
{
    use TranslatesResourceNavigation;

    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static \UnitEnum|string|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Activities';

    protected static ?string $modelLabel = 'Activity';

    protected static ?string $pluralModelLabel = 'Activities';

    public static function form(Schema $schema): Schema
    {
        return ActivityForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /**
     * Activity logs are immutable audit records; creation is system-driven only.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * Activity logs must never be edited; tampering would invalidate the audit trail.
     */
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    /**
     * Activity logs must never be deleted from the admin panel.
     */
    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * Bulk deletion of activity logs is prohibited.
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }
}
