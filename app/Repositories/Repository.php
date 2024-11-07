<?php

namespace App\Repositories;

use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

abstract class Repository
{
    protected ?Model $model;
    protected ?User $authUser;

    protected ?string $search_field = '';

    protected ?string $order_by = '';
    protected ?string $sort = '';

    protected $joins = [];

    public function getModel(): Model
    {
        return $this->model;
    }

    public function setModel($model): void
    {
        $this->model = $model;
    }

    public function setSearchField($field): void
    {
        $this->search_field = $field;
    }

    public function setSort($order_by, $sort): void
    {
        $this->order_by = $order_by;
        $this->sort = $sort;
    }

    public function init($params)
    {
        if (isset($params['model'])) {
            $this->setModel($params['model']);
        }
        if (isset($params['order_by']) && isset($params['sort'])) {
            $this->setSort($params['order_by'], $params['sort']);
        }
        if (isset($params['search_field'])) {
            $this->setSearchField($params['search_field']);
        }
    }

    public function get(Request $request): Collection
    {
        $model_name = $this->getModelName();
        if (empty($request->per_page)) {
            $request->merge(['per_page' => 50]);
        }
        if (empty($request->page)) {
            $request->merge(['page' => 1]);
        }

        $query = $this->applyFilters($request);
        $results = $this->getResults($request, $query);

        if (!empty($results[$model_name]) && $results[$model_name]->count() > 0) {
            return $results;
        } else {
            throw new \Exception(__('response.not_found'), 400);
        }
    }

    public function store(array $data): Collection
    {
        $data = $this->preProcessing('store', $data);
        $this->model->fill($data)->save();
        if ($this->model->id) {
            $this->model->refresh();
            $this->postProcessing('store', $data, $this->model);
            return static::show(['uuid' => $this->model->uuid]);
        }
        throw new \Exception(__('response.create_failed'), 999);
    }

    public function copy(string $uuid, array|null $data): Collection|bool
    {
        $original = $this->model::findByUuid($uuid);
        if (empty($original)) {
            throw new \Exception(__('response.not_found'), 400);
        }
        if (!empty($data['range'])) {
            if (empty($data['range']['from'])) {
                throw new \Exception('The range.from field is required.');
            } elseif (empty($data['range']['to'])) {
                throw new \Exception('The range.to field is required.');
            } elseif (empty($data['range']['interval'])) {
                throw new \Exception('The range.interval field is required.');
            }
            $period = new CarbonPeriod($data['range']['from'], $data['range']['interval'], $data['range']['to']);
            $date_name = $data['range']['name'];
            unset($data['range'], $data['amount']);
            foreach ($period as $date) {
                $data[$date_name] = $date;
                $new = $original->replicate();
                $new->uuid = null;
                $new->fill($data)->save();
                foreach ($original->details as $detail) {
                    $new->details()->save($detail);
                }
            }
            if ($new->id) {
                $new->refresh();
                return static::show(['uuid' => $new->uuid]);
            }
        } else {
            $new = $original->replicate();
            $new->uuid = null;
            if (!empty($data['range'])) {
                $new->fill($data);
            }
            $new->save();
            if (!empty($original->details)) {
                foreach ($original->details as $detail) {
                    $new->details()->save($detail);
                }
            }
            if ($new->id) {
                $new->refresh();
                return static::show(['uuid' => $new->uuid]);
            }
        }
        throw new \Exception(__('response.create_failed'), 999);
    }

    public function update(string $uuid, array $data): Collection
    {
        $result = $this->model::where('uuid', $uuid)->first();
        $data = $this->preProcessing('update', $data, $result);
        if ($result) {
            if ($result->fill($data)->save()) {
                $result = $this->postProcessing('update', $data, $result);
                return static::show(['uuid' => $result->uuid]);
            }
            throw new \Exception(__('response.update_failed'), 999);
        }
        throw new \Exception(__('response.not_found'), 400);
    }

    public function updateMultiple(Request $request): bool
    {
        $models = [];
        $data = $request->all();
        if (empty($data['uuid'])) {
            throw new \Exception(__('response.item_not_found'), 409);
        }
        if (empty($data['data'])) {
            throw new \Exception(__('response.item_not_found'), 409);
        }

        foreach ($data['uuid'] as $uuid) {
            $result = $this->model::where('uuid', $uuid)->first();
            if (empty($result)) {
                throw new \Exception(__('response.item_not_found'), 409);
            } else {
                $models[$uuid] = $result;
            }
        }
        foreach ($models as $model) {
            if (!$model->fill($data['data'])->save()) {
                throw new \Exception(__('response.update_failed'), 999);
            }
        }
        return true;
    }

    public function show(string|array|int $uuid): Collection
    {
        $model_name = $this->getModelName();
        if (is_array($uuid)) {
            if (!empty($uuid['id'])) {
                $uuid = $uuid['id'];
            } elseif (!empty($uuid['uuid'])) {
                $uuid = $uuid['uuid'];
            }
        }
        if (is_numeric($uuid)) {
            $request = request()->merge([
                'filters' => [
                    'id' => $uuid
                ]
            ]);
        } else {
            $request = request()->merge([
                'filters' => [
                    'uuid' => $uuid
                ]
            ]);
        }
        $result = $this->get($request);
        if (!empty($result[$model_name]) && $result[$model_name]->count() > 0) {
            return $result;
        } else {
            throw new \Exception(__('response.not_found'), 400);
        }
    }

    public function destroy(string $uuid, Request $request, bool $force = false): bool
    {
        if ($force) {
            $result = $this->model::withTrashed()->where('uuid', $uuid)->first();
        } else {
            $result = $this->model::where('uuid', $uuid)->first();
        }

        if (empty($result)) {
            throw new \Exception(__('response.not_found'), 400);
        }
        if (!empty($request->all())) {
            $result->fill($request->all())->save();
        }
        if ($force) {
            if (!$result->forceDelete()) {
                throw new \Exception(__('response.delete_failed'), 999);
            }
        } else {
            if (!$result->delete()) {
                throw new \Exception(__('response.delete_failed'), 999);
            }
        }
        return true;
    }

    public function destroyMultiple(Request $request, bool $force = false): bool
    {
        $models = [];
        $data = $request->all();
        foreach ($data as $uuid) {
            if ($force) {
                $result = $this->model::withTrashed()->where('uuid', $uuid)->first();
            } else {
                $result = $this->model::where('uuid', $uuid)->first();
            }

            if (empty($result)) {
                throw new \Exception(__('response.item_not_found'), 409);
            } else {
                $models[$uuid] = $result;
            }
        }
        foreach ($data as $uuid) {
            if ($force) {
                if (!$models[$uuid]->forceDelete()) {
                    throw new \Exception(__('response.delete_failed'), 999);
                }
            } else {
                if (!$models[$uuid]->delete()) {
                    throw new \Exception(__('response.delete_failed'), 999);
                }
            }
        }
        return true;
    }

    public function preProcessing(string $action, array $data, ?Model $model = null): array
    {
        $authUser = Auth::user();
        $data['updated_by_user_id'] = !empty($authUser) ? $authUser->id : null;
        if ($action == 'store') {
            $data['created_by_user_id'] = !empty($authUser) ? $authUser->id : null;
        }
        return $data;
    }

    public function postProcessing(string $action, array $data, ?Model $model): Model
    {
        return $model;
    }

    private function getModelName(): string
    {
        return strtolower((new \ReflectionClass($this->model))->getShortName());
    }

    public function applyFilters(Request $request): Builder
    {
        $model = $this->model;
        $search_field = !empty($this->search_field) ? $this->search_field : null;
        if (!empty($request->with)) {
            $model = $this->model::with($request->with);
        }
        if (!empty($request->without)) {
            $model = $this->model::without($request->without);
        }
        try {
            $modelTablePrefix = $table_name = $this->model->getTable();
            if ($modelTablePrefix) {
                $modelTablePrefix .= '.';
            }
        } catch (\Exception $e) {
            $modelTablePrefix = '';
        }
        $query = $model->where(function ($q) use ($request, $search_field, $modelTablePrefix) {
            if (!empty($request->filters['id'])) {
                $q->where($modelTablePrefix . 'id', $request->filters['id']);
            }
            if (!empty($request->filters['uuid'])) {
                $q->where($modelTablePrefix . 'uuid', $request->filters['uuid']);
            }
            if (!empty($request->filters['name'])) {
                $q->where($modelTablePrefix . 'name', $request->filters['name']);
            }
            if (!empty($request->filters['description'])) {
                $q->where($modelTablePrefix . 'description', 'like', "%{$request->filters['description']}%");
            }
            if (!empty($request->filters['search'])) {
                $search_field = !empty($search_field) ? $search_field : 'name';
                if (str_contains($search_field, '.')) {
                    $fields = explode('.', $search_field);
                    $q->whereHas($fields[0], function ($q1) use ($fields, $request) {
                        $q1->where($fields[1], 'like', "%{$request->filters['search']}%");
                    });
                } else {
                    $q->where($modelTablePrefix . $search_field, 'like', "%{$request->filters['search']}%");
                }
            }
            if (!empty($request->filters['created_at']['start'])) {
                $q->where($modelTablePrefix . 'created_at', '>=', $request->filters['created_at']['start']);
            }
            if (!empty($request->filters['created_at']['end'])) {
                $q->where($modelTablePrefix . 'created_at', '<=', $request->filters['created_at']['end']);
            }
            if (!empty($request->filters['updated_at']['start'])) {
                $q->where($modelTablePrefix . 'updated_at', '>=', $request->filters['updated_at']['start']);
            }
            if (!empty($request->filters['updated_at']['end'])) {
                $q->where($modelTablePrefix . 'updated_at', '<=', $request->filters['updated_at']['end']);
            }
            if (!empty($request->filters['is_deleted'])) {
                $q->withTrashed();
            }
        });
        if (!empty($request->filters)) {
            foreach ($request->filters as $name => $value) {
                if (strstr($name, '.') && $value != '') {
                    list($join_model, $field) = explode('.', $name);
                } else {
                    $join_model = $name;
                    if (is_array($value)) {
                        $field = key($value);
                        $value = $value[$field];
                    } else {
                        $join_model = $table_name;
                        $field = $name;
                    }
                }
                if ($join_model == $table_name) {
                    $fillable = array_merge(['uuid'], $this->model->getFillable());
                    if (in_array($field, $fillable)) {
                        if (in_array($field, ['name', 'description'])) {
                            $query->where($field, 'like', '%' . $value . '%');
                        } else {
                            $query->where($field, $value);
                        }
                    }
                } elseif (method_exists($this->model, $join_model)) {
                    $fillable = array_merge(['uuid'], $this->model->$join_model()->getQuery()->getModel()->getFillable());
                    if (in_array($field, $fillable)) {
                        $join_table_name = $this->model->$join_model()->getQuery()->getModel()->getTable();
                        $join_foreign_key = $this->model->$join_model()->getQuery()->getModel()->getForeignKey();
                        if ($join_model == 'created_by') {
                            $join_foreign_key = 'created_by_user_id';
                        } else if ($join_model == 'updated_by') {
                            $join_foreign_key = 'updated_by_user_id';
                        }
                        $query->join($join_table_name, $join_table_name . '.id', '=', $join_foreign_key);
                        if (in_array($field, ['name', 'description'])) {
                            $query->where($join_table_name . '.' . $field, 'like', '%' . $value . '%');
                        } else {
                            $query->where($join_table_name . '.' . $field, $value);
                        }
                        $this->joins[] = $join_table_name;
                        $query->select($modelTablePrefix . '*');
                    }
                } elseif ($field == 'start' || $field == 'end') {
                    if ($field == 'start') {
                        $query->where($join_model, '>=', $value);
                    } elseif ($field == 'end') {
                        $query->where($join_model, '<=', $value);
                    } else {
                        $query->where($field, $value);
                    }
                }
            }
        }
        //dd(HelperRepository::getSql($query));
        return $query;
    }

    public function getResults(Request $request, Builder $query): Collection
    {
        $model_name = $this->getModelName();
        $model_table_name = $this->model->getModel()->getTable();
        $results = collect([]);
        $order_by = !empty($request->orderby) ? $request->orderby : $this->order_by;
        $sort = !empty($request->sortby) ? $request->sortby : $this->sort;

        if (!empty($order_by)) {
            $sort_by = !empty($sort) ? $sort : 'asc';
            if (str_contains($order_by, '.')) {
                list($order_model, $order_by) = explode('.', $request->orderby);
                $join_table_name = $this->model->$order_model()->getQuery()->getModel()->getTable();
                $join_foreign_key = $this->model->$order_model()->getQuery()->getModel()->getForeignKey();
                if (!in_array($join_table_name, $this->joins)) {
                    $query->join($join_table_name, $join_table_name . '.id', '=', $join_foreign_key);
                }
                $order_by = $join_table_name . '.' . $order_by;
                $query->select($model_table_name . '.*');
            }
            $query->orderBy($order_by, $sort_by);
        }
        //dd(HelperRepository::getSql($query));
        $results[$model_name . '_count'] = $query->count();
        $results[$model_name] = $query->paginate($request->per_page)->appends([
            'per_page' => $request->per_page,
        ]);
        return $results;
    }

    public function showFile(string $bucket, string $uuid, string $field = 'filename'): string
    {
        if ($data = $this->model->findbyUuid($uuid)) {
            if (!Storage::disk($bucket)->exists($data->$field)) {
                throw new \Exception(__('response.not_found'), 400);
            }
            $output = Storage::disk($bucket)->get($data->$field);
            return base64_encode($output);
        } else {
            throw new \Exception(__('response.not_found'), 400);
        }
    }

    public function destroyFile(string $bucket, string $uuid, string $field = 'filename')
    {
        if ($data = $this->model->findbyUuid($uuid)) {
            if (!Storage::disk($bucket)->exists($data->$field)) {
                throw new \Exception(__('response.not_found'), 400);
            }
            Storage::disk($bucket)->delete($data->$field);
        } else {
            throw new \Exception(__('response.not_found'), 400);
        }
    }

    public function transform(Model $model): Model
    {
        unset($model->deleted_at, $model->pivot);
        return $model;
    }

    public function setAuthUser(?User $user): void
    {
        $this->authUser = $user;
    }
}
