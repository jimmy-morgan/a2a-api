<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\FindTrait;
use App\Traits\UserTrait;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use FindTrait, UserTrait;

    public $timestamps = false;
    protected $hidden = [
        'id',
        'pivot',
        'created_by_user_id',
        'updated_by_user_id',
    ];
    protected $fillable = [
        'name',
        'created_by_user_id',
        'updated_by_user_id',
    ];
    protected $casts = [
        'name' => 'string',
    ];

    public function permissions(): belongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): belongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
