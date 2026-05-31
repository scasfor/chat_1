<?php

namespace App\Filament\Widgets;

use App\Models\Conversation;
use Filament\Widgets\ChartWidget;

class ConversationsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Conversations - Last 30 Days';

    protected int | string | array $columnSpan = 'full';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = Conversation::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        $labels = [];
        $values = [];

        for ($i = 29; $i >= 0; $i--) {
            $date     = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('M d');
            $values[] = (int) ($counts[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Conversations',
                    'data'            => $values,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.75)',
                    'borderColor'     => 'rgb(245, 158, 11)',
                    'borderWidth'     => 1,
                    'borderRadius'    => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }
}
