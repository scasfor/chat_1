<?php

namespace App\Filament\Resources\UnmatchedQuestions;

use App\Filament\Resources\UnmatchedQuestions\Pages\ManageUnmatchedQuestions;
use App\Models\Intent;
use App\Models\UnmatchedQuestion;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnmatchedQuestionResource extends Resource
{
    protected static ?string $model = UnmatchedQuestion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Unmatched Questions';

    protected static ?string $pluralModelLabel = 'Unmatched Questions';

    protected static ?string $modelLabel = 'Unmatched Question';

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 0 ? 'danger' : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')
                    ->searchable()
                    ->wrap()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Reported At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->recordActions([
                Action::make('matchToIntent')
                    ->label('Match to Intent')
                    ->icon(Heroicon::OutlinedLink)
                    ->color('success')
                    ->modalHeading('Match to Existing Intent')
                    ->form(fn (UnmatchedQuestion $record): array => [
                        Placeholder::make('question_preview')
                            ->label('Question')
                            ->content($record->question),
                        Select::make('intent_id')
                            ->label('Intent')
                            ->options(
                                Intent::orderBy('title')->pluck('title', 'id')
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Match & Add as Phrase')
                    ->action(function (UnmatchedQuestion $record, array $data): void {
                        $intent = Intent::findOrFail($data['intent_id']);

                        $intent->phrases()->create([
                            'phrase' => $record->question,
                        ]);

                        $record->delete();

                        Notification::make()
                            ->title("Matched: phrase added to \"{$intent->title}\".")
                            ->success()
                            ->send();
                    }),

                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUnmatchedQuestions::route('/'),
        ];
    }
}
