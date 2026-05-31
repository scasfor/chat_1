<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Intent;
use App\Models\IntentKeyword;
use App\Models\IntentPhrase;
use Illuminate\Database\Seeder;

class GeneralConversationSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'General Conversation'],
            ['sort_order' => 0, 'status' => 1]
        );

        $intents = [
            [
                'intent_key' => 'greeting',
                'title'      => 'Greeting',
                'response'   => "Hello! 👋 How can I help you today?",
                'priority'   => 100,
                'phrases'    => [
                    'hello', 'hi', 'hey', 'good morning', 'good afternoon',
                    'good evening', 'hola', 'yo', "what's up", 'sup', 'howdy',
                ],
                'keywords'   => [
                    ['keyword' => 'hello',     'weight' => 10],
                    ['keyword' => 'hi',        'weight' => 10],
                    ['keyword' => 'hey',       'weight' => 9],
                    ['keyword' => 'morning',   'weight' => 7],
                    ['keyword' => 'afternoon', 'weight' => 7],
                    ['keyword' => 'evening',   'weight' => 7],
                    ['keyword' => 'hola',      'weight' => 8],
                    ['keyword' => 'yo',        'weight' => 6],
                    ['keyword' => 'sup',       'weight' => 7],
                    ['keyword' => 'howdy',     'weight' => 8],
                ],
            ],
            [
                'intent_key' => 'goodbye',
                'title'      => 'Goodbye',
                'response'   => "Goodbye! 👋 Feel free to return anytime if you need help.",
                'priority'   => 100,
                'phrases'    => [
                    'bye', 'goodbye', 'see you', 'see ya', 'talk later',
                    'have a nice day', 'take care', 'cya', 'good night', 'farewell',
                ],
                'keywords'   => [
                    ['keyword' => 'bye',      'weight' => 10],
                    ['keyword' => 'goodbye',  'weight' => 10],
                    ['keyword' => 'later',    'weight' => 7],
                    ['keyword' => 'farewell', 'weight' => 9],
                    ['keyword' => 'cya',      'weight' => 8],
                    ['keyword' => 'night',    'weight' => 6],
                    ['keyword' => 'care',     'weight' => 5],
                ],
            ],
            [
                'intent_key' => 'thanks',
                'title'      => 'Thanks',
                'response'   => "You're welcome! 😊 Is there anything else I can help you with?",
                'priority'   => 100,
                'phrases'    => [
                    'thanks', 'thank you', 'thank you very much', 'thanks a lot',
                    'appreciate it', 'many thanks', 'thx', 'ty', 'thank u',
                ],
                'keywords'   => [
                    ['keyword' => 'thanks',     'weight' => 10],
                    ['keyword' => 'thank',      'weight' => 10],
                    ['keyword' => 'appreciate', 'weight' => 9],
                    ['keyword' => 'grateful',   'weight' => 8],
                    ['keyword' => 'thx',        'weight' => 9],
                    ['keyword' => 'ty',         'weight' => 8],
                ],
            ],
            [
                'intent_key' => 'help',
                'title'      => 'Help Request',
                'response'   => "I can help you with APPUI support topics. What would you like to know?",
                'priority'   => 90,
                'phrases'    => [
                    'help', 'i need help', 'support', 'assist me', 'can you help me',
                    'need assistance', 'help me', 'i need assistance', 'please help',
                ],
                'keywords'   => [
                    ['keyword' => 'help',      'weight' => 10],
                    ['keyword' => 'support',   'weight' => 9],
                    ['keyword' => 'assist',    'weight' => 8],
                    ['keyword' => 'assistance','weight' => 8],
                    ['keyword' => 'please',    'weight' => 4],
                ],
            ],
            [
                'intent_key' => 'yes_confirmation',
                'title'      => 'Yes / Confirmation',
                'response'   => "Got it! Please go ahead.",
                'priority'   => 80,
                'phrases'    => [
                    'yes', 'yeah', 'yep', 'okay', 'ok', 'sure', 'correct',
                    'right', 'affirmative', "that's right", 'yup', 'of course',
                ],
                'keywords'   => [
                    ['keyword' => 'yes',    'weight' => 10],
                    ['keyword' => 'yeah',   'weight' => 9],
                    ['keyword' => 'yep',    'weight' => 9],
                    ['keyword' => 'okay',   'weight' => 8],
                    ['keyword' => 'ok',     'weight' => 7],
                    ['keyword' => 'sure',   'weight' => 8],
                    ['keyword' => 'yup',    'weight' => 9],
                    ['keyword' => 'course', 'weight' => 6],
                ],
            ],
            [
                'intent_key' => 'no_confirmation',
                'title'      => 'No / Denial',
                'response'   => "Understood! Let me know if you change your mind.",
                'priority'   => 80,
                'phrases'    => [
                    'no', 'nope', 'nah', 'cancel', 'never mind', 'no thanks',
                    'not really', 'negative', 'no way',
                ],
                'keywords'   => [
                    ['keyword' => 'no',     'weight' => 10],
                    ['keyword' => 'nope',   'weight' => 9],
                    ['keyword' => 'cancel', 'weight' => 8],
                    ['keyword' => 'nah',    'weight' => 8],
                    ['keyword' => 'never',  'weight' => 6],
                ],
            ],
        ];

        foreach ($intents as $data) {
            $intent = Intent::firstOrCreate(
                ['intent_key' => $data['intent_key']],
                [
                    'category_id' => $category->id,
                    'title'       => $data['title'],
                    'response'    => $data['response'],
                    'priority'    => $data['priority'],
                    'is_active'   => true,
                ]
            );

            foreach ($data['phrases'] as $phrase) {
                IntentPhrase::firstOrCreate([
                    'intent_id' => $intent->id,
                    'phrase'    => $phrase,
                ]);
            }

            foreach ($data['keywords'] as $kw) {
                IntentKeyword::firstOrCreate(
                    ['intent_id' => $intent->id, 'keyword' => $kw['keyword']],
                    ['weight' => $kw['weight']]
                );
            }
        }

        $this->command->info('General conversation intents seeded successfully.');
    }
}
