<?php

namespace App\Filament\Widgets;

use App\Enums\Role;
use App\Models\User;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentUsers extends BaseWidget
{
    protected static ?string $heading = 'Newest Users';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied'),

                TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst($state instanceof Role ? $state->value : (string) $state))
                    ->color(fn ($state): string => match ($state instanceof Role ? $state->value : (string) $state) {
                        'admin' => 'danger',
                        'editor' => 'warning',
                        default => 'primary',
                    }),

                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean(fn ($state): bool => filled($state)),

                TextColumn::make('profile_completion_score')
                    ->label('Profile')
                    ->formatStateUsing(fn ($state): string => ((int) $state).'%')
                    ->badge()
                    ->color(fn ($state): string => ((int) $state) >= 80 ? 'success' : (((int) $state) >= 50 ? 'warning' : 'gray')),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->since()
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25]);
    }
}
