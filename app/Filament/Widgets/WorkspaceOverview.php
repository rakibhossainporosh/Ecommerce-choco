<?php

namespace App\Filament\Widgets;

use App\Support\AdminDashboard;
use Filament\Widgets\Widget;

class WorkspaceOverview extends Widget
{
    protected string $view = 'filament.widgets.workspace-overview';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $hour = (int) now()->format('G');
        $greeting = match (true) {
            $hour < 12 => __('Good morning'),
            $hour < 18 => __('Good afternoon'),
            default => __('Good evening'),
        };

        $role = $user?->getRoleNames()->first();
        $hasWorkspace = AdminDashboard::canSeeOperationalStats($user)
            || AdminDashboard::shortcuts($user) !== [];

        return [
            'greeting' => $greeting,
            'name' => $user?->name ?? __('there'),
            'role' => $role ? (string) $role : null,
            'hasWorkspace' => $hasWorkspace,
        ];
    }
}
