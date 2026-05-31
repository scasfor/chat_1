<?php

namespace App\Filament\Widgets;

use App\Models\UnmatchedQuestion;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUnmatchedQuestionsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Unmatched Questions')
            ->query(
                UnmatchedQuestion::query()->latest()
            )
            ->columns([
                TextColumn::make('question')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Asked At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }
}
