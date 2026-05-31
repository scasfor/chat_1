<?php

namespace App\Filament\Resources\Intents;

use App\Filament\Resources\Intents\Pages\ManageIntents;
use App\Models\Category;
use App\Models\Intent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Reader\XLSX\Reader;

class IntentResource extends Resource
{
    protected static ?string $model = Intent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Category')
                    ->options(Category::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
                TextInput::make('intent_key')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('response')
                    ->required()
                    ->columnSpanFull(),
                // TextInput::make('priority')
                //     ->required()
                //     ->numeric()
                //     ->default(1),
                Toggle::make('is_active')
                    ->required(),
                Repeater::make('phrases')
                    ->relationship('phrases')
                    ->schema([
                        TextInput::make('phrase')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel('Add Phrase')
                    ->columnSpanFull(),
                Repeater::make('keywords')
                    ->relationship('keywords')
                    ->schema([
                        TextInput::make('keyword')
                            ->required(),
                        TextInput::make('weight')
                            ->numeric()
                            ->default(1)
                            ->required(),
                    ])
                    ->columns(2)
                    ->addActionLabel('Add Keyword')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('intent_key')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                // TextColumn::make('priority')
                //     ->numeric()
                //     ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('phrases')
                    ->label('Phrases')
                    ->icon(Heroicon::OutlinedChatBubbleLeft)
                    ->form([
                        Repeater::make('phrases')
                            ->schema([
                                TextInput::make('phrase')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->addActionLabel('Add Phrase')
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn(Intent $record): array => [
                        'phrases' => $record->phrases
                            ->map(fn($p) => ['phrase' => $p->phrase])
                            ->toArray(),
                    ])
                    ->action(function (Intent $record, array $data): void {
                        $record->phrases()->delete();
                        foreach ($data['phrases'] ?? [] as $item) {
                            $record->phrases()->create(['phrase' => $item['phrase']]);
                        }
                    }),
                Action::make('keywords')
                    ->label('Keywords')
                    ->icon(Heroicon::OutlinedTag)
                    ->form([
                        Repeater::make('keywords')
                            ->schema([
                                TextInput::make('keyword')
                                    ->required(),
                                TextInput::make('weight')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                            ])
                            ->columns(2)
                            ->addActionLabel('Add Keyword')
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn(Intent $record): array => [
                        'keywords' => $record->keywords
                            ->map(fn($k) => ['keyword' => $k->keyword, 'weight' => $k->weight])
                            ->toArray(),
                    ])
                    ->action(function (Intent $record, array $data): void {
                        $record->keywords()->delete();
                        foreach ($data['keywords'] ?? [] as $item) {
                            $record->keywords()->create([
                                'keyword' => $item['keyword'],
                                'weight'  => $item['weight'],
                            ]);
                        }
                    }),
                Action::make('followUpIntents')
                    ->label('Follow Up Intents')
                    ->icon(Heroicon::OutlinedArrowTurnDownRight)
                    ->form([
                        Select::make('follow_up_intent_ids')
                            ->label('Follow Up Intents')
                            ->options(fn(Intent $record) => Intent::where('id', '!=', $record->id)
                                ->orderBy('title')
                                ->pluck('title', 'id'))
                            ->multiple()
                            ->searchable()
                            ->columnSpanFull(),
                    ])
                    ->fillForm(fn(Intent $record): array => [
                        'follow_up_intent_ids' => $record->followUpIntents->pluck('id')->toArray(),
                    ])
                    ->action(function (Intent $record, array $data): void {
                        $record->followUpIntents()->sync($data['follow_up_intent_ids'] ?? []);
                    }),
                ActionGroup::make([
                    Action::make('importPhrases')
                        ->label('Import Phrases')
                        ->icon(Heroicon::OutlinedArrowUpTray)
                        ->modalHeading('Import Phrases from Excel')
                        ->form([
                            FileUpload::make('excel_file')
                                ->label('Excel File (.xlsx)')
                                ->disk('local')
                                ->directory('temp-imports')
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->required(),
                            Placeholder::make('format_hint')
                                ->label('')
                                ->content('Expected column: Phrase (first row is header and will be skipped)'),
                        ])
                        ->action(function (Intent $record, array $data): void {
                            $path = Storage::disk('local')->path($data['excel_file']);
                            $imported = 0;

                            try {
                                $reader = new Reader();
                                $reader->open($path);

                                foreach ($reader->getSheetIterator() as $sheet) {
                                    $rowIndex = 0;

                                    foreach ($sheet->getRowIterator() as $row) {
                                        $rowIndex++;

                                        if ($rowIndex === 1) {
                                            continue; // skip header
                                        }

                                        $cells = $row->getCells();
                                        $phrase = trim((string) ($cells[0]?->getValue() ?? ''));

                                        if ($phrase !== '') {
                                            $record->phrases()->create(['phrase' => $phrase]);
                                            $imported++;
                                        }
                                    }

                                    break; // first sheet only
                                }

                                $reader->close();
                            } finally {
                                Storage::disk('local')->delete($data['excel_file']);
                            }

                            Notification::make()
                                ->title("{$imported} phrase(s) imported successfully.")
                                ->success()
                                ->send();
                        }),

                    Action::make('importKeywords')
                        ->label('Import Keywords')
                        ->icon(Heroicon::OutlinedTag)
                        ->modalHeading('Import Keywords from Excel')
                        ->form([
                            FileUpload::make('excel_file')
                                ->label('Excel File (.xlsx)')
                                ->disk('local')
                                ->directory('temp-imports')
                                ->acceptedFileTypes([
                                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                ])
                                ->required(),
                            Placeholder::make('format_hint')
                                ->label('')
                                ->content('Expected columns: Keyword | Weight (first row is header and will be skipped; Weight defaults to 1 if omitted)'),
                        ])
                        ->action(function (Intent $record, array $data): void {
                            $path = Storage::disk('local')->path($data['excel_file']);
                            $imported = 0;

                            try {
                                $reader = new Reader();
                                $reader->open($path);

                                foreach ($reader->getSheetIterator() as $sheet) {
                                    $rowIndex = 0;

                                    foreach ($sheet->getRowIterator() as $row) {
                                        $rowIndex++;

                                        if ($rowIndex === 1) {
                                            continue; // skip header
                                        }

                                        $cells = $row->getCells();
                                        $keyword = trim((string) ($cells[0]?->getValue() ?? ''));

                                        if ($keyword === '') {
                                            continue;
                                        }

                                        $rawWeight = $cells[1]?->getValue() ?? 1;
                                        $weight = is_numeric($rawWeight) ? (int) $rawWeight : 1;

                                        $record->keywords()->create([
                                            'keyword' => $keyword,
                                            'weight'  => $weight,
                                        ]);
                                        $imported++;
                                    }

                                    break; // first sheet only
                                }

                                $reader->close();
                            } finally {
                                Storage::disk('local')->delete($data['excel_file']);
                            }

                            Notification::make()
                                ->title("{$imported} keyword(s) imported successfully.")
                                ->success()
                                ->send();
                        }),
                ])
                ->label('Import')
                ->icon(Heroicon::OutlinedArrowUpTray),

                EditAction::make(),
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
            'index' => ManageIntents::route('/'),
        ];
    }
}
