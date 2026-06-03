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
            ['name' => 'Conversación General'],
            ['sort_order' => 0, 'status' => 1]
        );

        $intents = [
            [
                'intent_key' => 'greeting',
                'title'      => 'Saludo',
                'response'   => "¡Hola! 👋 ¿Cómo puedo ayudarte hoy?",
                'priority'   => 100,
                'phrases'    => [
                    'hola', 'buenos días', 'buenas tardes', 'buenas noches',
                    'qué tal', 'saludos', 'hey', 'hola amigo',
                ],
                'keywords'   => [
                    ['keyword' => 'hola',            'weight' => 10],
                    ['keyword' => 'buenos',          'weight' => 7],
                    ['keyword' => 'días',            'weight' => 7],
                    ['keyword' => 'tardes',          'weight' => 7],
                    ['keyword' => 'noches',          'weight' => 7],
                    ['keyword' => 'saludos',         'weight' => 8],
                    ['keyword' => 'qué',             'weight' => 6],
                    ['keyword' => 'tal',             'weight' => 6],
                    ['keyword' => 'hey',             'weight' => 8],
                ],
            ],
            [
                'intent_key' => 'goodbye',
                'title'      => 'Despedida',
                'response'   => "¡Adiós! 👋 No dudes en volver cuando necesites ayuda.",
                'priority'   => 100,
                'phrases'    => [
                    'adiós', 'hasta luego', 'nos vemos', 'hasta pronto',
                    'que tengas un buen día', 'cuídate', 'hasta mañana',
                    'buenas noches',
                ],
                'keywords'   => [
                    ['keyword' => 'adiós',         'weight' => 10],
                    ['keyword' => 'luego',         'weight' => 8],
                    ['keyword' => 'vemos',         'weight' => 8],
                    ['keyword' => 'pronto',        'weight' => 8],
                    ['keyword' => 'cuídate',       'weight' => 9],
                    ['keyword' => 'mañana',        'weight' => 7],
                    ['keyword' => 'noches',        'weight' => 6],
                ],
            ],
            [
                'intent_key' => 'thanks',
                'title'      => 'Agradecimiento',
                'response'   => "¡De nada! 😊 ¿Hay algo más en lo que pueda ayudarte?",
                'priority'   => 100,
                'phrases'    => [
                    'gracias', 'muchas gracias', 'mil gracias',
                    'te lo agradezco', 'agradecido', 'agradecida',
                ],
                'keywords'   => [
                    ['keyword' => 'gracias',       'weight' => 10],
                    ['keyword' => 'agradezco',     'weight' => 9],
                    ['keyword' => 'agradecido',    'weight' => 8],
                    ['keyword' => 'agradecida',    'weight' => 8],
                    ['keyword' => 'muchas',        'weight' => 6],
                    ['keyword' => 'mil',           'weight' => 6],
                ],
            ],
            [
                'intent_key' => 'help',
                'title'      => 'Solicitud de Ayuda',
                'response'   => "Puedo ayudarte con temas de soporte de APPUI. ¿Qué te gustaría saber?",
                'priority'   => 90,
                'phrases'    => [
                    'ayuda', 'necesito ayuda', 'soporte',
                    'puedes ayudarme', 'necesito asistencia',
                    'ayúdame', 'tengo una pregunta',
                ],
                'keywords'   => [
                    ['keyword' => 'ayuda',         'weight' => 10],
                    ['keyword' => 'soporte',       'weight' => 9],
                    ['keyword' => 'asistencia',    'weight' => 8],
                    ['keyword' => 'ayúdame',       'weight' => 9],
                    ['keyword' => 'pregunta',      'weight' => 7],
                ],
            ],
            [
                'intent_key' => 'yes_confirmation',
                'title'      => 'Sí / Confirmación',
                'response'   => "¡Entendido! Puedes continuar.",
                'priority'   => 80,
                'phrases'    => [
                    'sí', 'claro', 'correcto', 'por supuesto',
                    'de acuerdo', 'vale', 'exacto',
                ],
                'keywords'   => [
                    ['keyword' => 'sí',            'weight' => 10],
                    ['keyword' => 'claro',         'weight' => 9],
                    ['keyword' => 'correcto',      'weight' => 9],
                    ['keyword' => 'acuerdo',       'weight' => 8],
                    ['keyword' => 'vale',          'weight' => 8],
                    ['keyword' => 'exacto',        'weight' => 9],
                ],
            ],
            [
                'intent_key' => 'no_confirmation',
                'title'      => 'No / Negación',
                'response'   => "¡Entendido! Avísame si cambias de opinión.",
                'priority'   => 80,
                'phrases'    => [
                    'no', 'no gracias', 'cancelar',
                    'olvídalo', 'para nada', 'negativo',
                ],
                'keywords'   => [
                    ['keyword' => 'no',            'weight' => 10],
                    ['keyword' => 'cancelar',      'weight' => 8],
                    ['keyword' => 'olvídalo',      'weight' => 8],
                    ['keyword' => 'negativo',      'weight' => 8],
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

        $this->command->info('Los intentos de conversación general se cargaron correctamente.');
    }
}