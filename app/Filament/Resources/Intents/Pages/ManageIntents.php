<?php

namespace App\Filament\Resources\Intents\Pages;

use App\Filament\Resources\Intents\IntentResource;
use App\Services\GeminiService;
use App\Services\IntentImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ManageIntents extends ManageRecords
{
    protected static string $resource = IntentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importIntents')
                ->label('Import from Excel')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalHeading('Import Intents from Excel')
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
                        ->content('Expected columns: Category ID | Question | Response (first row treated as header and skipped)'),
                ])
                ->action(function (array $data): void {
                    $path = Storage::disk('local')->path($data['excel_file']);

                    try {
                        $result = (new IntentImporter(new GeminiService()))->import($path);
                    } finally {
                        Storage::disk('local')->delete($data['excel_file']);
                    }

                    Notification::make()
                        ->title("Import complete: {$result['imported']} imported, {$result['failed']} failed.")
                        ->success()
                        ->send();

                    if (!empty($result['errors'])) {
                        Notification::make()
                            ->title('Some rows could not be imported')
                            ->body(implode("\n", array_slice($result['errors'], 0, 10)))
                            ->warning()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}

