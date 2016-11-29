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
use Illuminate\Support\Arr;
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

    protected function _newQuery()
    {
        $this->query = $this->modelInstance->newQuery();
        return $this->query;
    }

    protected function _getFlushQuery($method, $params = null)
    {
        if (!empty($params)) {
            $ret = call_user_func_array([$this->getQuery(), $method], $params);
        } else {
            $ret = call_user_func([$this->getQuery(), $method]);
        }
        $this->_flushQuery();
        return $ret;
    }

    protected function _flushQuery()
    {
        unset($this->query);
        $this->query = null;
    }

    public function where($where)
    {
        if (!Arr::isAssoc($where)) {

            $column = $where[0];
            $operator = $where[1];
            $value = $where[2];
            $logic = !empty($where[3]) ? $where[3] : 'and';

            if ($operator == 'in') {
                $this->getQuery()->whereIn($column, $value, $logic);
            } else {
                $this->getQuery()->where($column, $operator, $value, $logic);
            }
        }
        return $this;
    }

    protected function _prepareWheres($wheres)
    {
        $preparedWheres = [];
        if (Arr::isAssoc($wheres)) {
            foreach ($wheres as $column => $condition) {
                if (is_array($condition)) {
                    $where = array_merge([$column], $condition);
                } else {
                    $where = [$column, '=', $condition];
                }
                $preparedWheres[] = $where;
            }
        } else {
            $preparedWheres = $wheres;
        }
        return $preparedWheres;
    }

    public function wheres($wheres)
    {
        $preparedWheres = $this->_prepareWheres($wheres);
        foreach ($preparedWheres as $where) {
            $this->where($where);
        }
        return $this;
    }

    public function orWhere($where)
    {
        if (!Arr::isAssoc($where)) {
            $where[3] = 'or';
            $this->where($where);
        }
        return $this;
    }

    public function orWheres($wheres)
    {
        $preparedWheres = $this->_prepareWheres($wheres);
        foreach ($preparedWheres as $where) {
            $this->orWhere($where);
        }
        return $this;
    }

    public function select($columns)
    {
        if (is_string($columns)) {
            $this->getQuery()->selectRaw($columns);
        } else {
            $this->getQuery()->select($columns);
        }
        return $this;
    }

    public function limits($offset, $limit = 0)
    {
        $args = func_get_args();
        $offset = $limit = 0;
        if (count($args) == 1) {
            if (is_array($args[0])) {
                list($offset, $limit) = $args[0];
            } else {
                $limit = $args[0];
            }
        } elseif (count($args) == 2) {
            list($offset, $limit) = $args;
        }

        $this->getQuery()->offset($offset);
        $this->getQuery()->limit($limit);
        return $this;
    }

    public function orders($orders)
    {
        if (Arr::isAssoc($orders)) {
            foreach ($orders as $column => $order) {
                $this->getQuery()->orderBy($column, $order);
            }
        } else {
            foreach ($orders as $order) {
                $this->getQuery()->orderByRaw($order);
            }
        }
        return $this;
    }

    public function groups($groups, $rawSql = false)
    {
        if (!$rawSql) {
            foreach ($groups as $column) {
                $this->getQuery()->groupBy($column);
            }
        } else {
            foreach ($groups as $group) {
                $this->getQuery()->groupByRaw($group);
            }
        }
        return $this;
    }

    public function havings($havings)
    {
        foreach ($havings as $having) {
            if (is_string($having)) {
                $this->getQuery()->havingRaw($having);
            } elseif (is_array($having) && count($having) == 3) {
                $this->getQuery()->having($having[0], $having[1], $having[2]);
            }
        }
        return $this;
    }

    public function get()
    {
        return $this->_getFlushQuery('get');
    }

    public function first()
    {
        return $this->_getFlushQuery('first');
    }

    public function count()
    {
        return $this->_getFlushQuery('count');
    }

    public function pluck()
    {
        return $this->_getFlushQuery('pluck');
    }

    public function value()
    {
        return $this->_getFlushQuery('value');
    }

    public function paginate($numPerPage = 10, $pageName = 'p')
    {
        return $this->_getFlushQuery('paginate', [$numPerPage, ['*'], $pageName]);
    }

    public function simpleSelect($wheres, $columns = ['*'], $limits = 0, $orders = [], $groups = [], $havings = [])
    {
        if ($wheres) $this->wheres($wheres);

        if ($columns) $this->select($columns);

        if ($limits) $this->limits($limits);

        if ($orders) $this->orders($orders);

        if ($groups) $this->groups($groups);

        if ($havings) $this->havings($havings);

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

    public function delete($id = 0)
    {
        if (!empty($id)) { // 依据主键删除
            if (is_array($id)) {
                return call_user_func_array([$this->modelFullName, 'destroy'], [$id]);
            }
            return call_user_func([$this->modelFullName, 'destroy'], $id);
        }

        // 条件删除
        return $this->_getFlushQuery('delete');
    }

    public function update($id, $attributes)
    {
        $model = $this->findOrFail($id);
        if (!$model) {
            return false;
        }

        if (!$model->fill($attributes)->save()) {
            return false;
        }

        return $model;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->modelInstance, $method], $parameters);
    }
}
