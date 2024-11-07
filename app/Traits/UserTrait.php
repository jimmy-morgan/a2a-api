<?php

namespace App\Traits;

trait UserTrait
{
    public function created_by()
    {
        return $this->belongsTo('App\Models\User', 'created_by_user_id');
    }

    public function updated_by()
    {
        return $this->belongsTo('App\Models\User', 'updated_by_user_id');
    }
}
