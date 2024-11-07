<?php

namespace App\Repositories;

use App\Models\Movie;

class MovieRepository extends Repository
{
    public function __construct(Movie $model)
    {
        $this->init([
            'model' => $model,
            'order_by' => 'name',
            'sort' => 'asc'
        ]);
    }

}
