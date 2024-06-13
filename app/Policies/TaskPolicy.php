<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;


    public function before(User $user, $ability)
    {
        if ($user->role === 'manager') {
            return true;
        }
    }

    public function viewAny(User $user)
    {
        return true;
    }

    public function view(User $user, Task $task)
    {
        return $user->role === 'manager' || $task->assignees->contains($user);
    }

    public function create(User $user)
    {
        return $user->role === 'manager';
    }

    public function update(User $user, Task $task)
    {
        return $user->role === 'manager';
    }

    public function delete(User $user, Task $task)
    {
        return $user->role === 'manager';
    }
    public function updateStatus(User $user, Task $task)
    {
        return $user->role === 'manager' || $task->assignees->contains($user);
    }
    public function addDependencies(User $user, Task $task)
    {
        return $user->role === 'manager';
    }

    public function assignUser(User $user, Task $task)
    {
        return $user->role === 'manager';
    }
}
