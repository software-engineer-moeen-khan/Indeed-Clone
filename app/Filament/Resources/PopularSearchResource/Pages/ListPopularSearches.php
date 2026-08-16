<?php

namespace App\Filament\Resources\PopularSearchResource\Pages;

use App\Filament\Resources\PopularSearchResource;
use App\Models\PopularSearch;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPopularSearches extends ListRecords
{
    protected static string $resource = PopularSearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Add Popular Search')
                ->icon('heroicon-o-plus-circle')
                ->visible(fn (): bool => PopularSearch::query()->count() < 4),
        ];
    }
}
