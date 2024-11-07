<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\FindTrait;
use App\Scopes\UserScope;

class Session extends Model
{
    use FindTrait, HasFactory;

    public $timestamps = false;
    protected $hidden = [
        'session_id',
        'user_id',
    ];
    protected $fillable = [
        'id',
        'user_id',
        'ip_address',
        'user_agent',
        'payload',
        'last_activity'
    ];
    protected $casts = [
        'id' => 'string',
        'user_id' => 'int',
        'ip_address' => 'string',
        'user_agent' => 'string',
        'payload' => 'string',
        'last_activity' => 'int',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo('App\Models\User')->withoutGlobalScope(UserScope::class);
    }

    public function getLastActivityAttribute($value): ?string
    {
        $datetime = Carbon::createFromTimestamp($value);
        return !empty($datetime) ? $datetime->setTimezone('UTC')->toDateTimeString() : null;
    }

    public function setPayloadAttribute($value): void
    {
        $this->attributes['payload'] = json_encode($value);
    }

    public function getPayloadAttribute($value): ?array
    {
        return json_decode($value);
    }
}
