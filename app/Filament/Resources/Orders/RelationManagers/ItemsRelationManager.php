<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Produits commandés';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_name')
            ->columns([
                TextColumn::make('product_name')
                    ->label('Produit')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Quantité')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('unit_price')
                    ->label('Prix unitaire')
                    ->money('TND', locale: 'fr_TN')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('TND', locale: 'fr_TN')
                    ->sortable(),
            ]);
    }
}
