<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjectRole extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'label',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_required_roles', 'role_id', 'project_id');
    }
}
