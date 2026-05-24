<?php

namespace App\Http\Controllers;

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
}
