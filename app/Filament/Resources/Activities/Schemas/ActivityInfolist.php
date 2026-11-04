<?php

namespace App\Filament\Resources\Activities\Schemas;

use App\Support\UiText;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->extraAttributes(['class' => 'fi-unified-record-form'])
            ->components([
                Section::make('Activity Details')
                    ->icon(Heroicon::OutlinedClipboardDocumentList)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        TextEntry::make('log_name')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'default' => __('General log'),
                                null, '' => '',
                                default => __($state),
                            }),
                        TextEntry::make('description')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'created' => __('Created'),
                                'updated' => __('Updated'),
                                'deleted' => __('Deleted'),
                                'restored' => __('Restored'),
                                null, '' => '',
                                default => __($state),
                            }),
                        TextEntry::make('subject_type')
                            ->label('Subject')
                            ->formatStateUsing(function ($state, $record): string {
                                if (blank($state)) {
                                    return '—';
                                }

                                return UiText::line(class_basename((string) $state)).' #'.$record->subject_id;
                            }),
                        TextEntry::make('causer.name')
                            ->label('Causer')
                            ->formatStateUsing(fn ($state): string => $state === 'Super Admin' ? __('Super Admin') : (string) ($state ?? '')),
                        TextEntry::make('created_at')->dateTime(),
                    ]),
                Section::make('Properties')
                    ->icon(Heroicon::OutlinedQueueList)
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'fi-unified-record-section'])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        KeyValueEntry::make('properties.attributes')
                            ->label('New Attributes'),
                        KeyValueEntry::make('properties.old')
                            ->label('Old Attributes'),
                    ])
                    ->visible(fn ($record) => $record->properties?->count() > 0),
            ]);
    }
}
