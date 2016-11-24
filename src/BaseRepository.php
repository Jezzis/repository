<?php
/**
 * Created by PhpStorm.
 * User: szj
 * Date: 16/8/21
 * Time: 21:41
 */

namespace Zyts\Repositories;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseRepository implements BaseRepositoryContract
{
    /**
     * @var Model
     */
    protected $modelInstance;

    protected $modelFullName;

    protected $modelName;

    protected $modelNamespace;

    /**
     * @var Builder
     */
    protected $query;

    private $errMsgs = [];

    public function __construct($modelName, $modelNamespace = 'App\\Models\\')
    {
        $this->$modelName = $modelName;
        $this->modelNamespace = $modelNamespace;

        $this->modelFullName = rtrim($modelNamespace, '\\') . '\\' . $modelName;
        $this->modelInstance = new $this->modelFullName();

        if (env('APP_DEBUG')) {
            DB::enableQueryLog();
        }
    }

    public function addErrMsg($errMsg)
    {
        if (trim($errMsg)) {
            $this->errMsgs[] = $errMsg;
        }
    }

    public function getErrMsg()
    {
        if ($this->errMsgs)
            return array_pop($this->errMsgs);
        return null;
    }

    public function getSqlDump()
    {
        return DB::getQueryLog();
    }

    public function find($primaryKey, $columns = ['*'])
    {
        return $this->modelInstance->find($primaryKey, $columns);
    }

    public function findOrFail($primaryKey, $columns = ['*'])
    {
        return $this->modelInstance->findOrFail($primaryKey, $columns);
    }

    public function all($columns = ['*'])
    {
        return call_user_func_array([$this->modelFullName, 'all'], array($columns));
    }

    public function getQuery()
    {
        if (!$this->query) {
            $this->query = $this->modelInstance->newQuery();
        }
        return $this->query;
    }

    protected function newQuery()
    {
        $this->query = $this->modelInstance->newQuery();
        return $this->query;
    }

    public function wheres($wheres)
    {
        $this->getQuery();
        if (arr_is_assoc($wheres)) {
            foreach ($wheres as $column => $condition) {
                if (is_array($condition)) {
                    if ($condition[0] == 'in') {
                        $this->query->whereIn($column, $condition[1]);
                    } else {
                        $this->query->where($column, $condition[0], $condition[1]);
                    }
                } else {
                    $this->query->where($column, $condition);
                }
            }
        } else {
            foreach ($wheres as $condition) {
                if (count($condition) == 3) {
                    if ($condition[1] == 'in') {
                        $this->query->whereIn($condition[0], $condition[2]);
                    } else {
                        $this->query->where($condition[0], $condition[1], $condition[2]);
                    }
                } elseif (count($condition) == 2) {
                    $this->query->where($condition[0], $condition[1]);
                }
            }
        }
        return $this;
    }

    public function orWheres($wheres)
    {
        $this->getQuery();
        if (arr_is_assoc($wheres)) {
            foreach ($wheres as $column => $condition) {
                if (is_array($condition)) {
                    $this->query->orWhere($column, $condition[0], $condition[1]);
                } else {
                    $this->query->orWhere($column, $condition);
                }
            }
        } else {
            foreach ($wheres as $condition) {
                if (count($condition) == 3) {
                    $this->query->orWhere($condition[0], $condition[1], $condition[2]);
                } elseif (count($condition) == 2) {
                    $this->query->orWhere($condition[0], $condition[1]);
                }
            }
        }
        return $this;
    }

    public function select($columns)
    {
        $this->getQuery();
        if (is_string($columns)) {
            $this->query->selectRaw($columns);
        } else {
            $this->query->select($columns);
        }
        return $this;
    }

    public function limits($limits)
    {
        $this->getQuery();
        if (is_array($limits)) {
            $this->query->limit($limits[1])->offset($limits[0]);
        } else {
            $this->query->limit($limits);
        }
        return $this;
    }

    public function orders($orders)
    {
        $this->getQuery();
        if (arr_is_assoc($orders)) {
            foreach ($orders as $column => $order) {
                $this->query->orderBy($column, $order);
            }
        } else {
            foreach ($orders as $order) {
                $this->query->orderByRaw($order);
            }
        }
        return $this;
    }

    public function groups($groups)
    {
        $this->getQuery();
        if (arr_is_assoc()) {
            foreach ($groups as $column) {
                $this->query->groupBy($column);
            }
        } else {
            foreach ($groups as $group) {
                $this->query->groupByRaw($group);
            }
        }
        return $this;
    }

    public function havings($havings)
    {
        $this->getQuery();
        foreach ($havings as $having) {
            if (is_string($having)) {
                $this->query->havingRaw($having);
            } elseif (is_array($having) && count($having) == 3) {
                $this->query->having($having[0], $having[1], $having[2]);
            }
        }
        return $this;
    }

    public function get()
    {
        $this->getQuery();
        $ret = $this->query->get();
        $this->query = null;
        return $ret;
    }

    public function first()
    {
        $this->getQuery();
        $ret = $this->query->first();
        $this->query = null;
        return $ret;
    }

    public function count()
    {
        $this->getQuery();
        $ret = $this->query->count();
        $this->query = null;
        return $ret;
    }

    public function paginate($numPerPage = 10)
    {
        $this->getQuery();
        $ret = $this->query->paginate($numPerPage, ['*'], 'p');
        $this->query = null;
        return $ret;
    }

    public function simpleSelect($wheres, $columns = ['*'], $limits = 0, $orders = [], $groups = [], $havings = [])
    {
        $this->newQuery();

        if ($wheres) {
            $this->wheres($wheres);
        }

        if ($columns) {
            $this->select($columns);
        }

        if ($limits) {
            $this->limits($limits);
        }

        if ($orders) {
            $this->orders($orders);
        }

        if ($groups) {
            $this->groups($groups);
        }

        if ($havings) {
            $this->havings($havings);
        }

        return $this->get();
    }

    public function search($wheres, $columns = ['*'], $limits = 0, $orders = [], $groups = [], $havings = [])
    {
        return $this->simpleSelect($wheres, $columns, $limits, $orders, $groups, $havings);
    }

    public function create($attributes)
    {
        return call_user_func_array([$this->modelFullName, 'create'], array($attributes));
    }

    public function delete($id = null)
    {
        if (!empty($id)) { // 依据主键删除
            if (is_array($id)) {
                return call_user_func_array([$this->modelFullName, 'destroy'], [$id]);
            }
            return call_user_func_array([$this->modelFullName, 'destroy'], $id);
        }

        // 条件删除
        $this->getQuery();
        $ret = $this->query->delete();
        $this->query = null;
        return $ret;
    }

    public function update($id, $attributes)
    {
        $model = $this->findOrFail($id);
        if (!$model) {
            return false;
        }

        return $model->fill($attributes)->save();
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->modelInstance, $method], $parameters);
    }
}
