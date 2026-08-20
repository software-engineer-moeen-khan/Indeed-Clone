<?php

namespace App\Filament\Resources\JobListingResource\Pages;

use App\Filament\Resources\JobListingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobListing extends EditRecord
{
    protected static string $resource = JobListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->color('success')
                ->openUrlInNewTab()
                ->icon('heroicon-o-eye'),
            Actions\Action::make('list')
                ->color('info')
                ->icon('heroicon-o-arrow-turn-right-up')
                ->label('All Jobs')
                ->url(fn () => route('filament.geezap.resources.job-listings.index')),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->icon('heroicon-o-check-circle')
                ->label('Update Job Info'),
            $this->getCancelFormAction()
                ->icon('heroicon-o-x-circle')
                ->color('gray'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        foreach (['benefits', 'qualifications', 'responsibilities', 'skills'] as $field) {
            $data[$field] = $this->normalizeTags($data[$field] ?? []);
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (['benefits', 'qualifications', 'responsibilities', 'skills'] as $field) {
            $data[$field] = $this->normalizeTags($data[$field] ?? []);
        }

        return $data;
    }

    private function normalizeTags(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[;\r\n]+/', $value) ?: [];
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(
            array_map(static fn ($item): string => trim((string) $item), $value),
            static fn (string $item): bool => $item !== ''
        )));
    }
}
