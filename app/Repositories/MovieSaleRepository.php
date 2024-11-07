<?php

namespace App\Repositories;

use App\Models\MovieSale;

class MovieSaleRepository extends Repository
{
    public function __construct(MovieSale $model)
    {
        $this->init([
            'model' => $model,
            'order_by' => 'price',
            'sort' => 'desc'
        ]);
    }

}
