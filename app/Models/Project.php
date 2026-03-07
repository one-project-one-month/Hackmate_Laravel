<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'type',
        'created_by_user_id',
        'github_repo',
        'is_active',
        'like_count',
        'dislike_count',
    ];

    protected $hidden = [
        'like_count',
        'dislike_count',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function requiredRoles(): BelongsToMany
    {
        return $this->belongsToMany(ProjectRole::class, 'project_required_roles', 'project_id', 'role_id');
    }

    public function feed(): HasOne
    {
        return $this->hasOne(Feed::class);
    }
}
