<?php

namespace App\Filament\Resources\Intents;

use App\Filament\Resources\Intents\Pages\ManageIntents;
use App\Models\Category;
use App\Models\Intent;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                TextInput::make('priority')
                    ->required()
                    ->numeric()
                    ->default(1),
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
                TextColumn::make('priority')
                    ->numeric()
                    ->sortable(),
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
