<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * GET /api/tasks
     * Return all tasks ordered by newest first.
     */
    public function index(): JsonResponse
    {
        $tasks = Task::latest()->get();

        return response()->json([
            'data' => $tasks,
        ]);
    }

    /**
     * POST /api/tasks
     * Create and persist a new task.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string',
            'status'       => 'sometimes|in:todo,in_progress,done',
            'album_number' => 'required|string|max:50',
        ]);

        $task = Task::create($validated);

        return response()->json([
            'message' => 'Task created successfully.',
            'data'    => $task,
        ], 201);
    }

    /**
     * GET /api/tasks/{id}
     * Return a single task by ID. Returns 404 if not found.
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        return response()->json([
            'data' => $task,
        ]);
    }

    /**
     * PUT /api/tasks/{id}  — replace all fields
     * PATCH /api/tasks/{id} — update only provided fields
     * Both are handled by this method via 'sometimes' validation.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        $validated = $request->validate([
            'title'        => 'sometimes|required|string|max:255',
            'description'  => 'nullable|string',
            'status'       => 'sometimes|in:todo,in_progress,done',
            'album_number' => 'sometimes|required|string|max:50',
        ]);

        $task->update($validated);

        return response()->json([
            'message' => 'Task updated successfully.',
            'data'    => $task->fresh(),
        ]);
    }

    /**
     * DELETE /api/tasks/{id}
     * Remove a task from the database. Returns 204 No Content.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::findOrFail($id);

        $task->delete();

        return response()->json(null, 204);
    }
}
