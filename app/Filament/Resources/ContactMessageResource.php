<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationLabel = 'Contact Us';

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $modelLabel = 'Contact Message';

    protected static ?string $pluralModelLabel = 'Contact Messages';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Message Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('subject')
                            ->columnSpanFull()
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('message')
                            ->rows(9)
                            ->columnSpanFull()
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('Admin Workflow')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'new' => 'New',
                                'in_progress' => 'In Progress',
                                'resolved' => 'Resolved',
                            ])
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('read_at')
                            ->label('First Opened')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Internal Admin Note')
                            ->rows(5)
                            ->columnSpanFull()
                            ->maxLength(5000),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subject')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn (ContactMessage $record): string => $record->subject),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                        default => 'New',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'in_progress' => 'warning',
                        'resolved' => 'success',
                        default => 'danger',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'new' => 'New',
                        'in_progress' => 'In Progress',
                        'resolved' => 'Resolved',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Open'),
                Tables\Actions\Action::make('resolve')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (ContactMessage $record): bool => $record->status !== 'resolved')
                    ->action(fn (ContactMessage $record) => $record->update([
                        'status' => 'resolved',
                        'read_at' => $record->read_at ?? now(),
                    ])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
            'edit' => Pages\EditContactMessage::route('/{record}/edit'),
        ];
    }
}
