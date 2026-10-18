<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Pages\RequestDelegateSeat;
use App\Models\Delegate;
use App\Models\Scopes\ApprovedScope;
use App\Models\Scopes\CurrentlyScope;
use Filament\Widgets\Widget;

final class DelegateRequestWidget extends Widget
{
    protected static ?int $sort = -1;

    protected string $view = 'filament.widgets.delegate-request-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->canRequestDelegateSeat() ?? false;
    }

    /**
     * @return array{pendingCount: int, requestUrl: string}
     */
    protected function getViewData(): array
    {
        return [
            'pendingCount' => Delegate::withoutGlobalScopes([ApprovedScope::class, CurrentlyScope::class])
                ->pending()
                ->where('user_id', auth()->id())
                ->count(),
            'requestUrl' => RequestDelegateSeat::getUrl(),
        ];
    }
}
