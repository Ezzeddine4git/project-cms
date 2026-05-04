<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\HasDashboardLoadingPlaceholder;
use App\Models\OrderItem;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BestSellingProducts extends TableWidget
{
    use HasDashboardLoadingPlaceholder;

    protected static ?string $heading = 'Meilleures ventes';

    protected static ?int $sort = -10;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'xl' => 5,
    ];

    public function getPlaceholderHeight(): ?string
    {
        return '22rem';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => OrderItem::query()
                ->selectRaw('MIN(order_items.id) as id, product_id, product_name, SUM(quantity) as quantity_sold, SUM(total) as revenue')
                ->whereHas('order', fn ($query) => $query->where('status', '!=', 'annulee'))
                ->groupBy('product_id', 'product_name'))
            ->columns([
                TextColumn::make('product_name')->label('Produit')->searchable()->wrap(),
                TextColumn::make('quantity_sold')->label('Vendus')->numeric()->sortable(),
                TextColumn::make('revenue')->label('Revenu')->money('TND', locale: 'fr_TN')->sortable(),
            ])
            ->defaultSort('quantity_sold', 'desc')
            ->defaultKeySort(false)
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
