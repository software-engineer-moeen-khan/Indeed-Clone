<?php

namespace App\Filament\Widgets;

use App\Models\JobListing;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentJobs extends BaseWidget
{
    protected static ?string $heading = 'Recently Published Jobs';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(JobListing::query()->latest())
            ->columns([
                TextColumn::make('job_title')
                    ->label('Job')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->limit(42),

                TextColumn::make('employer_name')
                    ->label('Company')
                    ->searchable()
                    ->limit(28),

                TextColumn::make('country')
                    ->label('Country')
                    ->placeholder('Not specified')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('employment_type')
                    ->label('Type')
                    ->placeholder('Not specified')
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_remote')
                    ->label('Remote')
                    ->boolean(),

                TextColumn::make('views')
                    ->label('Views')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Published')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->defaultPaginationPageOption(5)
            ->paginationPageOptions([5, 10, 25]);
    }
}
