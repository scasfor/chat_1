<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Conversation;
use App\Models\Intent;
use App\Models\IntentKeyword;
use App\Models\IntentPhrase;
use App\Models\UnmatchedQuestion;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalIntents  = Intent::count();
        $activeIntents = Intent::where('is_active', true)->count();

        return [
            Stat::make('Total Intents', $totalIntents)
                ->description("{$activeIntents} active")
                ->descriptionIcon(Heroicon::OutlinedLightBulb)
                ->color('primary'),

            Stat::make('Conversations', Conversation::count())
                ->description('Total chatbot interactions')
                ->descriptionIcon(Heroicon::OutlinedChatBubbleLeft)
                ->color('success'),

            Stat::make('Unmatched Questions', UnmatchedQuestion::count())
                ->description('Needs attention')
                ->descriptionIcon(Heroicon::OutlinedQuestionMarkCircle)
                ->color('danger'),

            Stat::make('Categories', Category::count())
                ->descriptionIcon(Heroicon::OutlinedFolderOpen)
                ->color('info'),

            Stat::make('Training Phrases', IntentPhrase::count())
                ->descriptionIcon(Heroicon::OutlinedChatBubbleBottomCenter)
                ->color('warning'),

            Stat::make('Keywords', IntentKeyword::count())
                ->descriptionIcon(Heroicon::OutlinedTag)
                ->color('gray'),
        ];
    }
}
