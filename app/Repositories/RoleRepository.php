<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Model;

class RoleRepository extends Repository
{
    public function __construct(Role $model)
    {
        $this->init([
            'model' => $model,
            'order_by' => 'name',
            'sort' => 'asc'
        ]);
    }

    public function postProcessing(string $action, array $data, ?Model $model): Model
    {
        if (!empty($data['permissions'])) {
            $userPermissions = Permission::whereIn('uuid', $data['permissions'])->get();
            $model->permissions()->sync($userPermissions ?? []);
        }
        return parent::postProcessing($action, $data, $model);
    }

}
