<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvertisementResource\Pages;
use App\Models\Advertisement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdvertisementResource extends Resource
{
    protected static ?string $model = Advertisement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Advertisements';
    protected static ?string $modelLabel = 'Advertisement';
    protected static ?string $pluralModelLabel = 'Advertisements';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Advertisement Details')
                ->description('Choose where this advertisement should appear on Best Way Jobs.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Internal Name')
                        ->placeholder('e.g. Homepage Top Banner')
                        ->helperText('Only admins see this name.')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\Select::make('placement')
                        ->label('Website Position')
                        ->options(Advertisement::placements())
                        ->required()
                        ->searchable(),

                    Forms\Components\Select::make('type')
                        ->label('Ad Type')
                        ->options([
                            'image' => 'Image / Banner',
                            'code' => 'Custom Ad Code (AdSense etc.)',
                        ])
                        ->default('image')
                        ->required(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Priority / Order')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->maxValue(100)
                        ->helperText('Lower numbers appear first when multiple ads use the same position.'),
                ])
                ->columns(2),

            Forms\Components\Section::make('Image / Banner Ad')
                ->description('Use these fields when Ad Type is Image / Banner.')
                ->schema([
                    Forms\Components\FileUpload::make('image_path')
                        ->label('Banner Image')
                        ->image()
                        ->disk('public')
                        ->directory('advertisements')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->helperText('Recommended: wide banner image. Maximum 5 MB.'),

                    Forms\Components\TextInput::make('target_url')
                        ->label('Click URL')
                        ->url()
                        ->placeholder('https://example.com')
                        ->maxLength(500),

                    Forms\Components\TextInput::make('alt_text')
                        ->label('Image Alt Text')
                        ->maxLength(255),

                    Forms\Components\Toggle::make('open_in_new_tab')
                        ->label('Open link in new tab')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('Custom Ad Code')
                ->description('Paste trusted advertisement code here when Ad Type is Custom Ad Code. This can be used for providers such as Google AdSense.')
                ->schema([
                    Forms\Components\Textarea::make('custom_code')
                        ->label('HTML / JavaScript Ad Code')
                        ->rows(10)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Publishing')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Start Showing At')
                        ->native(false),

                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Stop Showing At')
                        ->native(false)
                        ->afterOrEqual('starts_at'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Advertisement')
                    ->searchable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('placement')
                    ->label('Position')
                    ->formatStateUsing(fn (string $state): string => Advertisement::placements()[$state] ?? $state)
                    ->badge(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'code' ? 'Ad Code' : 'Image'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Immediately'),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('No end date'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('placement')
                    ->label('Website Position')
                    ->options(Advertisement::placements()),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'image' => 'Image / Banner',
                        'code' => 'Custom Ad Code',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No advertisements yet')
            ->emptyStateDescription('Create an advertisement and choose where it should appear on the website.');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Advertisement::query()->where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvertisements::route('/'),
            'create' => Pages\CreateAdvertisement::route('/create'),
            'edit' => Pages\EditAdvertisement::route('/{record}/edit'),
        ];
    }
}
