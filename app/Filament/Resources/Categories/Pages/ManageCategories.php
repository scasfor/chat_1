<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected array $topicsToCreate = [];

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $this->topicsToCreate = $data['topics'] ?? [];
                    unset($data['topics']);
                    return $data;
                })
                ->after(function (Category $record): void {
                    foreach ($this->topicsToCreate as $topic) {
                        $record->topics()->create(['topic' => $topic]);
                    }
                    $this->topicsToCreate = [];
                }),
        ];
    }
}

