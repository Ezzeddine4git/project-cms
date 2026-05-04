<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Ventes';

    protected static ?string $modelLabel = 'commande';

    protected static ?string $pluralModelLabel = 'commandes';

    protected static ?string $recordTitleAttribute = 'order_number';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Order::query()->where('status', 'nouvelle')->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('Client')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('order_number')
                    ->label('Numéro')
                    ->required(),
                Select::make('status')
                    ->label('Statut')
                    ->options(Order::STATUSES)
                    ->required()
                    ->default('nouvelle'),
                TextInput::make('subtotal')
                    ->label('Sous-total')
                    ->required()
                    ->numeric()
                    ->suffix('TND'),
                TextInput::make('total')
                    ->label('Total')
                    ->required()
                    ->numeric()
                    ->suffix('TND'),
                TextInput::make('customer_name')
                    ->label('Nom client')
                    ->required(),
                TextInput::make('customer_email')
                    ->label('E-mail client')
                    ->email()
                    ->required(),
                TextInput::make('address_line')
                    ->label('Adresse'),
                TextInput::make('postal_code')
                    ->label('Code postal'),
                TextInput::make('city')
                    ->label('Ville'),
                TextInput::make('country')
                    ->label('Pays')
                    ->required()
                    ->default('Tunisie'),
                Textarea::make('notes')
                    ->label('Notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number')->label('Numéro'),
                TextEntry::make('status')->label('Statut')->formatStateUsing(fn (string $state): string => Order::STATUSES[$state] ?? $state),
                TextEntry::make('user.name')->label('Client'),
                TextEntry::make('customer_email')->label('E-mail'),
                TextEntry::make('total')->label('Total')->money('TND', locale: 'fr_TN'),
                TextEntry::make('created_at')->label('Créée le')->dateTime('d/m/Y H:i'),
                TextEntry::make('address_line')->label('Adresse')->placeholder('-'),
                TextEntry::make('postal_code')->label('Code postal')->placeholder('-'),
                TextEntry::make('city')->label('Ville')->placeholder('-'),
                TextEntry::make('country')->label('Pays'),
                TextEntry::make('notes')->label('Notes')->placeholder('-')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Commande')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'payee', 'terminee' => 'success',
                        'preparation', 'expediee' => 'warning',
                        'annulee' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('items_sum_quantity')
                    ->label('Produits vendus')
                    ->sum('items', 'quantity')
                    ->sortable(),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('TND', locale: 'fr_TN')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(Order::STATUSES),
            ])
            ->recordActions([
                ViewAction::make()->label('Voir'),
                EditAction::make()->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
