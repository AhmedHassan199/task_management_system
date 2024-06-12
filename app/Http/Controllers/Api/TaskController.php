<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Task::class);

        $query = Task::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('due_date_from') && $request->has('due_date_to')) {
            $query->whereBetween('due_date', [$request->due_date_from, $request->due_date_to]);
        }

        if ($request->has('user_id')) {
            $query->whereHas('assignees', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $this->authorize('create', Task::class);
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'required|nullable|date',
            'status' => 'in:pending,completed,canceled',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);
        $task = Task::create($request->all());
        // return response()->json["message" => "task created successfully " ,$task, 201];
        return response()->json(["message" => "Successfully task created", 'status' => 201, 'task' => $task]);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load('dependencies', 'assignees');
        return response()->json($task);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'in:pending,completed,canceled',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);

        $task->update($request->all());

        if ($request->has('assignees')) {
            $task->assignees()->sync($request->assignees);
        }

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();
        return response()->json(null, 204);
    }

    public function getTasksByUser(User $user)
    {
        $this->authorize('view', $user);

        $tasks = $user->tasks()->with('dependencies', 'assignees')->get();

        return response()->json($tasks);
    }
}
