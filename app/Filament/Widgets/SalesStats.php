<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardLoadingPlaceholder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\Money;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStats extends StatsOverviewWidget
{
    use HasDashboardLoadingPlaceholder;

    protected static ?int $sort = -20;

    public function getPlaceholderHeight(): ?string
    {
        return '8rem';
    }

    protected function getStats(): array
    {
        $validOrders = Order::query()->where('status', '!=', 'annulee');
        $totalRevenue = (float) (clone $validOrders)->sum('total');
        $totalOrders = (clone $validOrders)->count();
        $soldProducts = OrderItem::query()
            ->whereHas('order', fn ($query) => $query->where('status', '!=', 'annulee'))
            ->sum('quantity');

        return [
            Stat::make('Produits vendus', number_format((int) $soldProducts, 0, ',', ' '))
                ->description('Articles confirmés')
                ->color('success'),
            Stat::make('Ventes globales', number_format($totalOrders, 0, ',', ' '))
                ->description('Commandes non annulées')
                ->color('warning'),
            Stat::make('Revenu total', Money::format($totalRevenue))
                ->description('Paiements simulés confirmés')
                ->color('success'),
            Stat::make('Commandes totales', Order::count())
                ->description('Tous statuts confondus')
                ->color('gray'),
        ];
    }
}
