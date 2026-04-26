<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        Task::query()->delete();

        Task::insert([
            [
                'title' => 'Prepare cache design note',
                'description' => 'Document what is cached and why',
                'status' => 'todo',
                'priority' => 'high',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Benchmark repeated GET requests',
                'description' => 'Compare cold and warm reads',
                'status' => 'doing',
                'priority' => 'medium',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Add deployment diagram',
                'description' => 'Show Nginx, Laravel, Redis, and PostgreSQL',
                'status' => 'done',
                'priority' => 'low',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
