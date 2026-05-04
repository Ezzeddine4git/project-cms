<?php

namespace App\Filament\Resources\HomePageSections;

use App\Filament\Resources\HomePageSections\Pages\CreateHomePageSection;
use App\Filament\Resources\HomePageSections\Pages\EditHomePageSection;
use App\Filament\Resources\HomePageSections\Pages\ListHomePageSections;
use App\Models\HomePageSection;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class HomePageSectionResource extends Resource
{
    protected static ?string $model = HomePageSection::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Contenu';

    protected static ?string $navigationLabel = 'Page d’accueil';

    protected static ?string $modelLabel = 'section d’accueil';

    protected static ?string $pluralModelLabel = 'sections d’accueil';

    protected static ?string $recordTitleAttribute = 'key';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Clé technique')
                    ->helperText('Exemples : hero, story, promo. Les sections existantes sont déjà reliées au site.')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Ordre')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('title')
                    ->label('Titre')
                    ->maxLength(255),
                Textarea::make('subtitle')
                    ->label('Sous-titre')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('body')
                    ->label('Texte')
                    ->rows(6)
                    ->columnSpanFull(),
                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->directory('home')
                    ->columnSpanFull(),
                TextInput::make('primary_label')
                    ->label('Libellé bouton principal'),
                TextInput::make('primary_url')
                    ->label('Lien bouton principal')
                    ->placeholder('/produits'),
                TextInput::make('secondary_label')
                    ->label('Libellé bouton secondaire'),
                TextInput::make('secondary_url')
                    ->label('Lien bouton secondaire')
                    ->placeholder('/blog'),
                KeyValue::make('settings')
                    ->label('Réglages avancés')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Ordre')
                    ->sortable(),
                TextColumn::make('key')
                    ->label('Clé')
                    ->searchable(),
                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(42),
                ImageColumn::make('image')
                    ->label('Image'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Mise à jour')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
            ])
            ->recordActions([
                EditAction::make()->label('Modifier'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHomePageSections::route('/'),
            'create' => CreateHomePageSection::route('/create'),
            'edit' => EditHomePageSection::route('/{record}/edit'),
        ];
    }
}
