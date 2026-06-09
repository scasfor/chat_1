<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\IntentMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function __construct(private readonly IntentMatcher $matcher) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message'    => ['required', 'string', 'max:1000'],
            'session_id' => ['sometimes', 'string', 'max:255'],
        ]);

        $sessionId = $data['session_id'] ?? Str::uuid()->toString();

        $result = $this->matcher->match($data['message'], $sessionId);

        return response()->json(array_merge($result, ['session_id' => $sessionId]));
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->where('name', '!=', Category::GENERAL_CONVERSATION_NAME)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->with(['intents' => fn ($query) => $query
                ->where('is_active', true)
                ->orderByDesc('priority')
                ->orderBy('title')])
            ->get()
            ->map(fn (Category $category) => [
                'id'       => $category->id,
                'name'     => $category->name,
                'question' => $category->intents->pluck('title')->values()->all(),
            ]);

        return response()->json($categories);
    }
}
