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
        $authUser = Auth::user();
        $query = Task::query();
        if ($authUser->role == 'user') {
            $query->whereHas('assignees', function ($q) use ($authUser) {
                $q->where('user_id', $authUser->id);
            });
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('due_date_from') && $request->has('due_date_to')) {
            $query->whereBetween('due_date', [$request->due_date_from, $request->due_date_to]);
        }
        if ($request->has('user_id') &&  $authUser->role == 'manager') {
            $query->whereHas('assignees', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }
        // list tasks with( dependencies , assignees)
        $tasks = $query->with('dependencies', 'assignees')->get();
        // list tasks without( dependencies , assignees)
        // $tasks = $query->get();
        // dd($tasks);
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
        return response()->json(["message" => "Successfully task created", 'status' => 201, 'task' => $task]);
    }

    public function show(Task $task)
    {
        $this->authorize('view', $task);

        $task->load('dependencies', 'assignees');
        return response()->json(["message" => "Successfully show task ", 'status' => 200, 'task' => $task]);
    }

    public function update(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated =  $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'status' => 'in:pending,completed,canceled',
            'parent_id' => 'nullable|exists:tasks,id',
            'assignees' => 'nullable|array',
            'assignees.*' => 'exists:users,id',
        ]);
        $task->update($request->all());
        if ($request->has('assignees')) {
            $task->assignees()->sync($validated['assignees']);
        }
        $task->load('dependencies', 'assignees');
        return response()->json($task);
    }
    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('updateStatus', $task);

        $validated = $request->validate([
            'status' => 'required|in:pending,completed,canceled',
        ]);

        $task->status = $validated['status'];
        $task->save();

        return response()->json($task);
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);
        if (!$task) {
            return response()->json(["error" => "Task not found"], 404);
        }
        $task->delete();
        return response()->json(["message" => "Successfully deleted task", 'status' => 204]);
    }


    public function getTasksByUser(User $user)
    {
        $this->authorize('view', $user);

        $tasks = $user->tasks()->with('dependencies', 'assignees')->get();

        return response()->json($tasks);
    }
    public function assignTaskToUsers(Request $request, Task $task)
    {
        $this->authorize('assignUser', Task::class);

        if (!$task) {
            return response()->json(["error" => "Task not found"], 404);
        }
        $validated = $request->validate([
            'users' => 'required|array',
            'users.*' => 'integer|exists:users,id',
        ]);
        $task->users()->sync($validated['users']);
        return response()->json(['message' => 'Task assigned to users successfully']);
    }
}
