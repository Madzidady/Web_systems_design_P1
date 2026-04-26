<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function index(): JsonResponse
    {
        $tasks = Cache::remember('tasks.index', 60, function () {
            return Task::query()->latest()->get();
        });

        return response()->json([
            'items' => $tasks,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:todo,doing,done'],
            'priority' => ['required', 'in:low,medium,high'],
        ]);

        $task = Task::create($validated);

        Cache::forget('tasks.index');

        return response()->json($task, 201);
    }

    public function show(Task $task): JsonResponse
    {
        return response()->json($task);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:todo,doing,done'],
            'priority' => ['sometimes', 'in:low,medium,high'],
        ]);

        $task->update($validated);

        Cache::forget('tasks.index');

        return response()->json($task);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        Cache::forget('tasks.index');

        return response()->json(null, 204);
    }
}
