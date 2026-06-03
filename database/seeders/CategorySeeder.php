<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Acceso y Configuración',
                'topics' => 'Acceso, creación de usuarios, correos habilitados',
                'sort_order' => 1,
            ],
            [
                'name' => 'Cargue de Información',
                'topics' => 'Plazos, no reporte, CSV, duplicados, período cerrado, CUIPO',
                'sort_order' => 2,
            ],
            [
                'name' => 'Corrección de Reportes',
                'topics' => 'Inhabilitación de registros, cargues erróneos',
                'sort_order' => 3,
            ],
            [
                'name' => 'Usuarios y Responsabilidades',
                'topics' => 'Número de usuarios, responsable de cargue',
                'sort_order' => 4,
            ],
            [
                'name' => 'Plataformas y Herramientas',
                'topics' => 'Relación con SFTP, FileZilla, CAS, SIRECI, CHIP, FUT',
                'sort_order' => 5,
            ],
            [
                'name' => 'Calendarios y Vigencias',
                'topics' => 'Año de inicio, información acumulada',
                'sort_order' => 6,
            ],
            [
                'name' => 'Tipos de Reportes',
                'topics' => 'Qué reportar, contratos, obras, liquidación',
                'sort_order' => 7,
            ],
            [
                'name' => 'Prórrogas y Sanciones',
                'topics' => 'Solicitud de prórroga, consecuencias de incumplimiento',
                'sort_order' => 8,
            ],
            [
                'name' => 'Soporte y Capacitación',
                'topics' => 'Manuales, videotutoriales, Mesa de Servicios',
                'sort_order' => 9,
            ],
        ];

        foreach ($categories as $category) {
            $category = \App\Models\Category::create($category);

            $topics = explode(',', $category->topics);

            foreach ($topics as $topic) {
                \App\Models\Topic::create([
                    'category_id' => $category->id,
                    'name' => trim($topic),
                ]);
            }
        }
    }
}
