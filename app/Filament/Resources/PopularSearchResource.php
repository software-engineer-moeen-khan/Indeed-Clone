<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PopularSearchResource\Pages;
use App\Models\PopularSearch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PopularSearchResource extends Resource
{
    protected static ?string $model = PopularSearch::class;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationLabel = 'Popular Searches';
    protected static ?string $modelLabel = 'Popular Search';
    protected static ?string $pluralModelLabel = 'Popular Searches';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Popular Search')
                ->description('Manage the four quick-search links shown below the homepage search form.')
                ->schema([
                    Forms\Components\TextInput::make('label')
                        ->label('Display Label')
                        ->helperText('The text visitors will see on the homepage.')
                        ->placeholder('e.g. Laravel')
                        ->required()
                        ->maxLength(80),

                    Forms\Components\TextInput::make('search_query')
                        ->label('Search Keyword')
                        ->helperText('This value will be sent to the Jobs search when the visitor clicks the label.')
                        ->placeholder('e.g. Laravel Developer')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\Select::make('sort_order')
                        ->label('Display Order')
                        ->options([
                            1 => '1 - First',
                            2 => '2 - Second',
                            3 => '3 - Third',
                            4 => '4 - Fourth',
                        ])
                        ->required()
                        ->default(1),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Show on homepage')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('label')
                    ->label('Display Label')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('search_query')
                    ->label('Search Keyword')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Homepage')
                    ->boolean(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No popular searches yet')
            ->emptyStateDescription('Add up to four quick searches for the homepage.');
    }

    public static function canCreate(): bool
    {
        return PopularSearch::query()->count() < 4;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) PopularSearch::query()->where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPopularSearches::route('/'),
            'create' => Pages\CreatePopularSearch::route('/create'),
            'edit' => Pages\EditPopularSearch::route('/{record}/edit'),
        ];
    }
}
