<?php

namespace App\Filament\Widgets\Concerns;

use Illuminate\Contracts\View\View;

trait HasDashboardLoadingPlaceholder
{
    public function placeholder(): View
    {
        return view('filament.widgets.dashboard-loading-placeholder', [
            'height' => $this->getPlaceholderHeight(),
            ...$this->getPlaceholderData(),
        ]);
    }
}
