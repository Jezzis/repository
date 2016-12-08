<?php
/**
 * Created by PhpStorm.
 * User: szj
 * Date: 16/8/21
 * Time: 23:34
 */
namespace Zyts\Repositories;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface BaseRepositoryContract
 * @package Zyts\Repositories
 *
 * @method \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null find(mixed $id, array $columns = ['*'])
 * @method \Illuminate\Database\Eloquent\Collection findMany(array $ids, array $columns = ['*'])
 * @method \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection findOrFail(mixed $id, array $columns = ['*'])
 * @method \Illuminate\Database\Eloquent\Model findOrNew(mixed $id, array $columns = ['*'])
 * @method \Illuminate\Database\Eloquent\Model firstOrNew(array $attributes)
 * @method \Illuminate\Database\Eloquent\Model firstOrCreate(array $attributes)
 * @method \Illuminate\Database\Eloquent\Model updateOrCreate(array $attributes, array $values = [])
 * @method boolean chunk(integer $count, callable $callback)
 */
interface BaseRepositoryContract
{
    /**
     * 添加一条错误消息
     * @param string $errMsg
     * @return mixed
     */
    public function addErrMsg($errMsg);

    /**
     * 获取最近一条错误消息
     * @return mixed
     */
    public function getErrMsg();

    /**
     * dump sql
     * @return mixed
     */
    public function getSqlDump();

    /**
     * alias to simpleSelect
     * @see simpleSelect
     */
    public function search($wheres, $columns = ['*'], $limits = 0, $orders = [], $groups = [], $havings = []);

    /**
     * 简单查询
     *
     * example:
     *  // 基于字段多条件查询
     *  repo->simpleSelect(['department' => 'test', 'age' => ['>', 30]]);
     *
     *  // 重复字段多条件查询
     *  repo->simpleSelect([['age', '<', 50], ['age', '>', 30]]]);
     *
     *  // 仅查询个别字段
     *  repo->simpleSelect(['department' => 'test'], ['age', 'name']);
     *
     *  // 限制条目数
     *  repo->simpleSelect(['department' => 'test'], ['*'], 15); // 限制15条
     *  repo->simpleSelect(['department' => 'test'], ['*'], [10, 15]); // 限制15条,从第11条开始
     *
     *  // 排序
     *  repo->simpleSelect(['department' => 'test'], ['*'], 15, ['age' => 'desc']); // 按年龄正向排序
     *
     * @param array $wheres 条件
     * @param array $columns 字段
     * @param int|array $limits 限制
     * @param array $orders 排序
     * @param array $groups 分组
     * @param array $havings 分组条件
     * @return mixed
     */
    public function simpleSelect($wheres, $columns = ['*'], $limits = 0, $orders = [], $groups = [], $havings = []);

    /**
     * 列出所有
     * @param array $columns 查询列
     * @return mixed
     */
    public function all($columns = ['*']);

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery();

    /**
     * 添加 where and 条件
     *
     * example:
     *
     *  where age >= '18'
     *  repo->where(['age', '>=', 18]);
     *
     *  where age = '18'
     *  repo->where(['age', '=', 18])
     *
     * @param array $where
     * @return BaseRepositoryContract
     */
    public function where($where);

    /**
     * 批量添加 where and 条件
     *
     * example:
     *
     *  1) where department = 'test' and age > '30'
     *  repo->wheres(['department' => 'test', 'age' => ['>', 30]]);
     *
     *  2) where age >= '18' and age < '30'
     *  repo->wheres([
     *      ['age', '>=', 18],
     *      ['age', '<', 30]
     *  ]);
     *
     * @param array $wheres 条件
     * @return BaseRepositoryContract
     */
    public function wheres($wheres);

    /**
     * 添加 where or 条件
     *
     * example:
     *
     *  where age >= '18'
     *  repo->orWhere(['age', '>=', 18]);
     *
     *  where age = '18'
     *  repo->orWhere(['age', '=', 18])
     *
     * @param array $where
     * @return BaseRepositoryContract
     */
    public function orWhere($where);

    /**
     * 批量添加 where or 条件
     *
     * example:
     *
     *  1) where department = 'test' or age > 30
     *  repo->orWheres(['department' => 'test', 'age' => ['>', 30]]);
     *  repo->orWheres([
     *      ['department', '=', 'test'],
     *      ['age', '>', 30]
     *  ]);
     *
     *  2) where department in ('text', 'develop') or
     *  repo->orWheres(['department' => ['in', ['test', 'develop']]]);
     *  repo->orWheres([
     *      ['department', 'in', ['test', 'develop']]
     *  ]);
     *
     * @param array $wheres 条件
     * @return BaseRepositoryContract
     */
    public function orWheres($wheres);

    /**
     * 添加查询字段
     *
     * example:
     *
     *  1) select columnA, columnB
     *  repo->select(['columnA', 'columnB'])
     *  repo->select('columnA, columnB')
     *
     *  2) select columnA as columnC, columnB as columnD
     *  repo->select('columnA as columnC, columnB as columnD')
     *
     * @param array|numeric $columns 查询字段
     * @return BaseRepositoryContract
     */
    public function select($columns);

    /**
     * 添加limit限制条件
     *
     * example:
     *
     *  1) limit 3 offset 1
     *  repo->limits(1, 3)
     *  repo->limits([1, 3])
     *
     *  2) limit 3 (offset 0)
     *  repo->limits(3)
     *
     * @param array|integer $offset
     * @param integer $limit
     * @return BaseRepositoryContract
     */
    public function limits($offset, $limit = 0);

    /**
     * 添加排序条件
     *
     * example:
     *
     *  order by columnA asc, columnB desc
     *  repo->orders(['columnA' => 'asc', 'columnB' => 'desc'])
     *  repo->orders(['columnA asc', 'columnB desc'])
     *
     * @param array $orders 排序
     * @return BaseRepositoryContract
     */
    public function orders($orders);

    /**
     * 添加分组条件
     *
     * example:
     *
     *  group by columnA
     *  repo->groups(['columnA'])
     *
     *  group by count(columnA)
     *  repo->groups(['count(columnA)'], true)
     *
     * @param array $groups 分组
     * @param boolean $rawSql 是否使用原生sql
     * @return BaseRepositoryContract
     */
    public function groups($groups, $rawSql = false);

    /**
     * 添加分组过滤条件
     *
     * example:
     *
     *  having columnA > 3, columnB > 1
     *  repo->havings([
     *      ['columnA', '=', 3],
     *      ['columnB', '=', 1]
     *  ])
     *
     *  repo->havings(['columnA > 3', 'columnB > 1'])
     * @param array $havings 分组过滤条件
     * @return mixed
     */
    public function havings($havings);

    /**
     * 获取结果集合
     *
     * @return Collection
     */
    public function get();

    /**
     * 获取单个对象结果
     *
     * @return Model
     */
    public function first();

    /**
     * 获取查询结果个数
     *
     * @return integer
     */
    public function count();

    /**
     * 获取单列
     *
     * @param $column
     * @return array
     */
    public function pluck($column);

    /**
     * 获取单个值
     *
     * @param $column
     * @return array
     */
    public function value($column);

    /**
     * 分页
     *
     * @param integer $numPerPage 每页多少
     * @param string $pageName Http Get 分页参数名
     * @return LengthAwarePaginator
     */
    public function paginate($numPerPage = 10, $pageName = 'p');

    /**
     * 创建
     * @param array $attributes 字段
     * @return Model
     */
    public function create($attributes);

    /**
     * 根据主键删除
     * @param mixed $id 主键|主键数组
     * @return integer 影响行数
     */
    public function delete($id = 0);

    /**
     * 根据主键更新
     * @param $id 主键
     * @param array $attributes 字段
     * @return Model|boolean
     */
    public function update($id, $attributes);
}