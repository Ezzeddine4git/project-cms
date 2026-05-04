<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardLoadingPlaceholder;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentOrders extends TableWidget
{
    use HasDashboardLoadingPlaceholder;

    protected static ?int $sort = -5;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'xl' => 7,
    ];

    public function getPlaceholderHeight(): ?string
    {
        return '22rem';
    }

    protected static ?string $heading = 'Commandes récentes';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Order::query()->with('user')->latest())
            ->columns([
                TextColumn::make('order_number')->label('Commande')->searchable(),
                TextColumn::make('user.name')->label('Client')->searchable(),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => Order::STATUSES[$state] ?? $state),
                TextColumn::make('total')->label('Total')->money('TND', locale: 'fr_TN'),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('voir')
                    ->label('Voir')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.view', $record)),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
