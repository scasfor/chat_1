<?php

namespace App\Filament\Resources\UnmatchedQuestions\Pages;

use App\Filament\Resources\UnmatchedQuestions\UnmatchedQuestionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUnmatchedQuestions extends ManageRecords
{
    protected static string $resource = UnmatchedQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
