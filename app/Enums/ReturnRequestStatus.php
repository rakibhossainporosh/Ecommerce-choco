<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReturnRequestStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Received = 'received';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __('enums.return_status.'.$this->value);
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Received => 'primary',
            self::Rejected => 'danger',
            self::Cancelled => 'gray',
            self::Completed => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Pending => 'heroicon-o-clock',
            self::Approved => 'heroicon-o-check-circle',
            self::Received => 'heroicon-o-archive-box',
            self::Rejected => 'heroicon-o-x-circle',
            self::Cancelled => 'heroicon-o-no-symbol',
            self::Completed => 'heroicon-o-check-badge',
        };
    }
}
