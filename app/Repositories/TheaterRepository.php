<?php

namespace App\Repositories;

use App\Models\Theater;

class TheaterRepository extends Repository
{
    public function __construct(Theater $model)
    {
        $this->init([
            'model' => $model,
            'order_by' => 'name',
            'sort' => 'asc'
        ]);
    }

}
