<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'due_date',
        'status',
        'parent_id'
    ];

    public function assignees()
    {
        return $this->belongsToMany(User::class, 'task_user');
    }

    public function dependencies()
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
