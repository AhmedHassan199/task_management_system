<?php


namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        // return response()->json($query->get());
        return ApiResponseHelper::success(TaskResource::collection($tasks));
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
        try {
            $task = Task::create($request->all());
            return ApiResponseHelper::success(new TaskResource($task), 'Successfully created task', 201);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('Task creation failed', 500, ['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $task = Task::findOrFail($id);
            $this->authorize('view', $task);
            $task->load('dependencies', 'assignees');
            return ApiResponseHelper::success(new TaskResource($task), 'Successfully retrieved task', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponseHelper::error('Task not found', 404);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occurred', 500, ['error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $task = Task::findOrFail($id);
            $this->authorize('update', $task);

            $validated = $request->validate([
                'title' => 'string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date',
                'status' => 'in:pending,completed,canceled',
                'parent_id' => 'nullable|exists:tasks,id',
                'assignees' => 'nullable|array',
                'assignees.*' => 'exists:users,id',
            ]);

            $task->update($validated);

            if ($request->has('assignees')) {
                $task->assignees()->sync($validated['assignees']);
            }

            $task->load('dependencies', 'assignees');

            return ApiResponseHelper::success(new TaskResource($task), 'Successfully updated task', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponseHelper::error('Task not found', 404);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('Task update failed', 500, ['error' => $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $task = Task::findOrFail($id);
            $this->authorize('updateStatus', $task);

            $validated = $request->validate([
                'status' => 'required|in:pending,completed,canceled',
            ]);

            $task->status = $validated['status'];
            $task->save();

            return ApiResponseHelper::success(new TaskResource($task), 'Successfully updated task status', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponseHelper::error('Task not found', 404);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('Task status update failed', 500, ['error' => $e->getMessage()]);
        }
    }

    /**
     * delete task by authorized user
     */
    public function destroy(Request $request)
    {
        $this->authorize('delete', Task::class);

        $request->validate([
            'task_id' => 'required|exists:tasks,id',
        ], [
            'task_id.exists' => 'The specified task does not exist.',
        ]);

        try {
            $task = Task::findOrFail($request->task_id);
            $task->delete();

            return ApiResponseHelper::success(null, 'The task has been deleted successfully', 200);
        } catch (ModelNotFoundException $e) {
            return ApiResponseHelper::error('Task not found', 404);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('Failed to delete task', 500, ['error' => $e->getMessage()]);
        }
    }
    public function addDependencies(Request $request, $taskId)
    {
        $this->authorize('addDependencies', Task::class);


        $request->validate([
            'assigned_tasks' => 'required|array',
            'assigned_tasks.*' => 'exists:tasks,id',
        ]);

        try {
            $dependenceTask = Task::findOrFail($taskId);

            $assignedTaskIds = $request->input('assigned_tasks');

            $assignedTasks = Task::whereIn('id', $assignedTaskIds)->get();

            foreach ($assignedTasks as $assignedTask) {
                $assignedTask->update(['parent_id' => $dependenceTask->id]);
            }

            return ApiResponseHelper::success(null, 'Tasks successfully assigned dependencies');
        } catch (ModelNotFoundException $e) {
            return ApiResponseHelper::error('Dependent or assigned task not found', 404);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('Failed to assign dependencies', 500, ['error' => $e->getMessage()]);
        }
    }

    public function assignTaskToUsers(Request $request, $taskId)
    {
        try {
            $this->authorize('assignUser', Task::class);

            // Validate that the task exists by its ID
            $task = Task::findOrFail($taskId);

            $validated = $request->validate([
                'users' => 'required|array',
                'users.*' => 'integer|exists:users,id',
            ]);

            $task->users()->sync($validated['users']);

            return ApiResponseHelper::success(null, 'Task assigned to users successfully');
        } catch (ModelNotFoundException $e) {
            return ApiResponseHelper::error('Task not found', 404);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('Failed to assign users to task', 500, ['error' => $e->getMessage()]);
        }
    }
}
