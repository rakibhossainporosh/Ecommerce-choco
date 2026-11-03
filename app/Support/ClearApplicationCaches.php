<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Throwable;

class ClearApplicationCaches
{
    /**
     * Flush Laravel optimization caches for the whole application.
     *
     * `optimize:clear` already includes Filament's optimize-clear via the
     * framework optimize registry — do not call filament:optimize-clear again.
     *
     * @return array{ok: bool, commands: list<string>, failed: string|null, message: string|null}
     */
    public function handle(): array
    {
        try {
            Artisan::call('optimize:clear');
        } catch (Throwable $exception) {
            report($exception);

            return [
                'ok' => false,
                'commands' => [],
                'failed' => 'optimize:clear',
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'ok' => true,
            'commands' => ['optimize:clear'],
            'failed' => null,
            'message' => null,
        ];
    }
}
