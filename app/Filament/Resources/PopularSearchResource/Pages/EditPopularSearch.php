<?php

namespace App\Filament\Resources\PopularSearchResource\Pages;

use App\Filament\Resources\PopularSearchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPopularSearch extends EditRecord
{
    protected static string $resource = PopularSearchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
