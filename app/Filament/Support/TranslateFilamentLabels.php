<?php

namespace App\Filament\Support;

use App\Support\UiText;
use Filament\Actions\Action;
use Filament\Infolists\Components\Entry;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use ReflectionClass;

class TranslateFilamentLabels
{
    public static function register(): void
    {
        $translate = function (object $component): void {
            if (! method_exists($component, 'translateLabel')) {
                return;
            }

            // Still enable Filament label translation; SafeTranslator guarantees
            // bare keys like "Payments" never resolve to language-file arrays.
            $component->translateLabel();
        };

        Component::configureUsing($translate);
        Entry::configureUsing($translate);
        Column::configureUsing($translate);
        Action::configureUsing($translate);
        BaseFilter::configureUsing($translate);

        Section::configureUsing(function (Section $section): void {
            $property = (new ReflectionClass($section))->getProperty('heading');
            $heading = $property->getValue($section);

            if (is_string($heading) && $heading !== '') {
                $section->heading(fn (): string => UiText::line($heading));
            }
        });
    }
}
