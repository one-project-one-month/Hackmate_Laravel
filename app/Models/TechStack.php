<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TechStack extends Model
{
    use HasFactory;

    protected $table = 'tech_stacks';

    protected $fillable = [
        'name',
        'category',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_tech_stack', 'tech_stack_id', 'user_id');
    }
}
