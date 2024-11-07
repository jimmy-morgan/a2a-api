<?php

namespace App\Models;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FindTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Movie extends Model
{
    use SoftDeletes, FindTrait, HasFactory;
    
    protected $dates = ['deleted_at'];
    protected $hidden = [
        'id',
        'deleted_at',
    ];
    protected $fillable = [
        'name',
        'description',
    ];

    public function theaters(): hasMany
    {
        return $this->hasMany(MovieSale::class);
    }
}
