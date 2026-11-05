<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Activities\ActivityResource;
use App\Support\UiText;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Spatie\Activitylog\Models\Activity;

class LatestActivities extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can('activities.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Activity::query()->with('causer')->latest()->limit(8)
            )
            ->heading(__('Recent Activity'))
            ->headerActions([
                Action::make('view_all')
                    ->label(__('View All Activities'))
                    ->icon('heroicon-m-arrow-right')
                    ->url(ActivityResource::getUrl('index')),
            ])
            ->columns([
                TextColumn::make('description')
                    ->label(__('Event'))
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'created' => __('Created'),
                        'updated' => __('Updated'),
                        'deleted' => __('Deleted'),
                        'restored' => __('Restored'),
                        null, '' => '—',
                        default => __($state),
                    })
                    ->badge()
                    ->color('gray'),

                TextColumn::make('subject_type')
                    ->label(__('Subject'))
                    ->alignCenter()
                    ->formatStateUsing(function ($state, Activity $record): string {
                        if (blank($state)) {
                            return '—';
                        }

                        return UiText::line(class_basename((string) $state)).' #'.$record->subject_id;
                    }),

                TextColumn::make('causer.name')
                    ->label(__('By'))
                    ->alignCenter()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(__('When'))
                    ->alignCenter()
                    ->since()
                    ->tooltip(fn (Activity $record): string => $record->created_at?->translatedFormat('M d, Y h:i A') ?? ''),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Activity $record): string => ActivityResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
