<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JoinRequest extends Model
{
    use HasFactory;

    protected $table = 'join_requests';

    protected $fillable = [
        'project_id',
        'user_id',
        'status',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkPermission($userId)
    {
        return $this->project && $this->project->created_by_user_id === $userId;
    }

    public function approve($userId)
    {
        if ($this->checkPermission($userId)) {
            $this->status = 'approved';
            $this->save();
            $this->project?->users()->syncWithoutDetaching([$this->user_id]);

            return true;
        }

        return false;
    }

    public function disapprove($userId)
    {
        if ($this->checkPermission($userId)) {
            $this->status = 'disproved';
            $this->save();

            return true;
        }

        return false;
    }

    public function listUserIds()
    {
        return self::where('project_id', $this->project_id)
            ->pluck('user_id')
            ->toArray();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeDisapproved($query)
    {
        return $query->where('status', 'disapproved');
    }
}
