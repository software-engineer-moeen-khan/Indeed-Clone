<?php

namespace App\Filament\Resources\JobListingResource\Pages;

use App\Filament\Resources\JobListingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJobListing extends CreateRecord
{
    protected static string $resource = JobListingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
