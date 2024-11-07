<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use App\Scopes\UserScope;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\FindTrait;
use App\Traits\UserTrait;

class User extends Authenticatable
{
    use SoftDeletes, FindTrait, HasApiTokens, HasFactory, Notifiable, UserTrait;

    protected $dates = [
        'expires_at',
        'lastlogin_at',
        'deleted_at',
    ];
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'cell_phone',
        'email_verify_token',
        'sms_verify_token',
        'login_count',
        'login_failed_attempts',
        'is_pw_reset_required',
        'is_email_verified',
        'is_sms_verified',
        'is_locked',
        'is_active',
        'email_verified_at',
        'expires_at',
        'lastlogin_at',
    ];
    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'pivot',
        'deleted_at',
        'created_by_user_id',
        'updated_by_user_id',
    ];
    protected $casts = [
        'first_name' => 'string',
        'last_name' => 'string',
        'email' => 'string',
        'password' => 'string',
        'cell_phone' => 'string',
        'email_verify_token' => 'string',
        'sms_verify_token' => 'string',
        'login_count' => 'int',
        'login_failed_attempts' => 'int',
        'is_pw_reset_required' => 'boolean',
        'is_email_verified' => 'boolean',
        'is_sms_verified' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
        'expires_at' => 'datetime',
        'lastlogin_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::addGlobalScope(new UserScope);
    }

    public function sessions(): hasMany
    {
        return $this->hasMany('App\Models\Session');
    }

    public function permissions(): belongsToMany
    {
        return $this->belongsToMany('App\Models\Permission');
    }

    public function roles(): belongsToMany
    {
        return $this->belongsToMany('App\Models\Role');
    }

    public function timezone():BelongsTo
    {
        return $this->belongsTo('App\Models\Timezone');
    }

    public function payees(): hasMany
    {
        return $this->hasMany('App\Models\Payee', 'created_by_user_id');
    }

    public function accounts(): hasMany
    {
        return $this->hasMany('App\Models\Account', 'created_by_user_id');
    }

    public function categories(): hasMany
    {
        return $this->hasMany('App\Models\Category', 'created_by_user_id');
    }

    public function mappings(): hasMany
    {
        return $this->hasMany('App\Models\Mapping', 'created_by_user_id');
    }

    public function transactions(): hasMany
    {
        return $this->hasMany('App\Models\Transaction', 'created_by_user_id');
    }

    public function setPasswordAttribute($value)
    {
        return $this->attributes['password'] = Hash::make($value);
    }
}
