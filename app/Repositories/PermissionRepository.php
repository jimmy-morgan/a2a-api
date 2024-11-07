<?php

namespace App\Repositories;

use App\Models\Permission;

class PermissionRepository extends Repository
{
    public function __construct(Permission $model)
    {
        $this->init([
            'model' => $model,
            'order_by' => 'name',
            'sort' => 'asc'
        ]);
    }

}
