<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

class AdminAccountWidget extends AccountWidget
{
    protected static ?int $sort = -30;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int | string | array $columnSpan = 'full';
}
