<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\Width;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tableau de bord';

    protected Width | string | null $maxContentWidth = Width::Full;

    /**
     * @return int | array<string, int | null>
     */
    public function getColumns(): int | array
    {
        return [
            'default' => 1,
            'xl' => 12,
        ];
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return [
            'cms-dashboard-page',
        ];
    }
}
