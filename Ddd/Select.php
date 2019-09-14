<?php

declare(strict_types=1);

/*
 * This file is part of the ************************ package.
 * _____________                           _______________
 *  ______/     \__  _____  ____  ______  / /_  _________
 *   ____/ __   / / / / _ \/ __`\/ / __ \/ __ \/ __ \___
 *    __/ / /  / /_/ /  __/ /  \  / /_/ / / / / /_/ /__
 *      \_\ \_/\____/\___/_/   / / .___/_/ /_/ .___/
 *         \_\                /_/_/         /_/
 *
 * The PHP Framework For Code Poem As Free As Wind. <Query Yet Simple>
 * (c) 2010-2019 http://queryphp.com All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Leevel\Database\Ddd;

use Closure;
use Leevel\Collection\Collection;
use Leevel\Database\Ddd\Relation\Relation;
use Leevel\Database\Select as DatabaseSelect;
use function Leevel\Support\Arr\normalize;
use Leevel\Support\Arr\normalize;
use function Leevel\Support\Str\contains;
use Leevel\Support\Str\contains;
use function Leevel\Support\Str\starts_with;
use Leevel\Support\Str\starts_with;

/**
 * 模型实体查询.
 *
 * @author Xiangmin Liu <635750556@qq.com>
 *
 * @since 2017.07.10
 *
 * @version 1.0
 *
 * @method static pdo($master = false)                                                                                                         返回 Pdo 查询连接.
 * @method static query(string $sql, array $bindParams = [], $master = false, int $fetchType = 5, $fetchArgument = null, array $ctorArgs = []) 查询数据记录.
 * @method static execute(string $sql, array $bindParams = [])                                                                                 执行 sql 语句.
 * @method static transaction(\Closure $action)                                                                                                执行数据库事务
 * @method static void beginTransaction()                                                                                                      启动事务.
 * @method static bool inTransaction()                                                                                                         检查是否处于事务中.
 * @method static void commit()                                                                                                                用于非自动提交状态下面的查询提交.
 * @method static void rollBack()                                                                                                              事务回滚.
 * @method static string lastInsertId(?string $name = null)                                                                                    获取最后插入 ID 或者列.
 * @method static string getLastSql()                                                                                                          获取最近一次查询的 sql 语句.
 * @method static int numRows()                                                                                                                返回影响记录.
 * @method static void close()                                                                                                                 关闭数据库.
 * @method static void freePDOStatement()                                                                                                      释放 PDO 预处理查询.
 * @method static void closeConnects()                                                                                                         关闭数据库连接.
 * @method static string normalizeExpression(string $sql, string $tableName)                                                                   sql 表达式格式化.
 * @method static string normalizeTableOrColumn(string $name, ?string $alias = null, ?string $as = null)                                       表或者字段格式化（支持别名）.
 * @method static string normalizeColumn(string $key, string $tableName)                                                                       字段格式化.
 * @method static normalizeColumnValue($value, bool $quotationMark = true)                                                                     字段值格式化.
 * @method static string normalizeSqlType(string $sql)                                                                                         分析 sql 类型数据.
 * @method static int normalizeBindParamType($value)                                                                                           分析绑定参数类型数据.
 * @method static string parseDsn(array $option)                                                                                               dsn 解析.
 * @method static array tableNames(string $dbName, $master = false)                                                                            取得数据库表名列表.
 * @method static array tableColumns(string $tableName, $master = false)                                                                       取得数据库表字段信息.
 * @method static string identifierColumn($name)                                                                                               sql 字段格式化.
 * @method static string limitCount(?int $limitCount = null, ?int $limitOffset = null)                                                         分析 limit.
 * @method static \Leevel\Database\Condition databaseCondition()                                                                               查询对象.
 * @method static \Leevel\Database\IDatabase databaseConnect()                                                                                 返回数据库连接对象.
 * @method static \Leevel\Database\Ddd\Select selfDatabaseSelect()                                                                             占位符返回本对象.
 * @method static \Leevel\Database\Ddd\Select sql(bool $flag = true)                                                                           指定返回 SQL 不做任何操作.
 * @method static \Leevel\Database\Ddd\Select master(bool $master = false)                                                                     设置是否查询主服务器.
 * @method static \Leevel\Database\Ddd\Select fetchArgs(int $fetchStyle, $fetchArgument = null, array $ctorArgs = [])                          设置查询参数.
 * @method static \Leevel\Database\Ddd\Select asClass(string $className, array $args = [])                                                     设置以类返会结果.
 * @method static \Leevel\Database\Ddd\Select asDefault()                                                                                      设置默认形式返回.
 * @method static \Leevel\Database\Ddd\Select asCollection(bool $acollection = true)                                                           设置是否以集合返回.
 * @method static select($data = null, array $bind = [], bool $flag = false)                                                                   原生 sql 查询数据 select.
 * @method static insert($data, array $bind = [], bool $replace = false, bool $flag = false)                                                   插入数据 insert (支持原生 sql).
 * @method static insertAll(array $data, array $bind = [], bool $replace = false, bool $flag = false)                                          批量插入数据 insertAll.
 * @method static update($data, array $bind = [], bool $flag = false)                                                                          更新数据 update (支持原生 sql).
 * @method static updateColumn(string $column, $value, array $bind = [], bool $flag = false)                                                   更新某个字段的值
 * @method static updateIncrease(string $column, int $step = 1, array $bind = [], bool $flag = false)                                          字段递增.
 * @method static updateDecrease(string $column, int $step = 1, array $bind = [], bool $flag = false)                                          字段减少.
 * @method static delete(?string $data = null, array $bind = [], bool $flag = false)                                                           删除数据 delete (支持原生 sql).
 * @method static truncate(bool $flag = false)                                                                                                 清空表重置自增 ID.
 * @method static findOne(bool $flag = false)                                                                                                  返回一条记录.
 * @method static findAll(bool $flag = false)                                                                                                  返回所有记录.
 * @method static find(?int $num = null, bool $flag = false)                                                                                   返回最后几条记录.
 * @method static value(string $field, bool $flag = false)                                                                                     返回一个字段的值
 * @method static pull(string $field, bool $flag = false)                                                                                      返回一个字段的值(别名).
 * @method static array list($fieldValue, ?string $fieldKey = null, bool $flag = false)                                                        返回一列数据.
 * @method static void chunk(int $count, \Closure $chunk)                                                                                      数据分块处理.
 * @method static void each(int $count, \Closure $each)                                                                                        数据分块处理依次回调.
 * @method static findCount(string $field = '*', string $alias = 'row_count', bool $flag = false)                                              总记录数.
 * @method static findAvg(string $field, string $alias = 'avg_value', bool $flag = false)                                                      平均数.
 * @method static findMax(string $field, string $alias = 'max_value', bool $flag = false)                                                      最大值.
 * @method static findMin(string $field, string $alias = 'min_value', bool $flag = false)                                                      最小值.
 * @method static findSum(string $field, string $alias = 'sum_value', bool $flag = false)                                                      合计.
 * @method static array page(int $currentPage, int $perPage = 10, bool $flag = false, bool $withTotal = true, string $column = '*')            分页查询.
 * @method static array page(int $currentPage, int $perPage = 10, bool $flag = false, string $column = '*', array $option = [])                分页查询. 可以渲染 HTML.
 * @method static array pageMacro(int $currentPage, int $perPage = 10, bool $flag = false, array $option = [])                                 创建一个无限数据的分页查询.
 * @method static array pagePrevNext(int $currentPage, int $perPage = 10, bool $flag = false, array $option = [])                              创建一个只有上下页的分页查询.
 * @method static int pageCount(string $cols = '*')                                                                                            取得分页查询记录数量.
 * @method static string makeSql(bool $withLogicGroup = false)                                                                                 获得查询字符串.
 * @method static \Leevel\Database\Ddd\Select forPage(int $page, int $perPage = 15)                                                            根据分页设置条件.
 * @method static \Leevel\Database\Ddd\Select time(string $type = 'date')                                                                      时间控制语句开始.
 * @method static \Leevel\Database\Ddd\Select endTime()                                                                                        时间控制语句结束.
 * @method static \Leevel\Database\Ddd\Select reset(?string $option = null)                                                                    重置查询条件.
 * @method static \Leevel\Database\Ddd\Select prefix(string $prefix)                                                                           prefix 查询.
 * @method static \Leevel\Database\Ddd\Select table($table, $cols = '*')                                                                       添加一个要查询的表及其要查询的字段.
 * @method static string getAlias()                                                                                                            获取表别名.
 * @method static \Leevel\Database\Ddd\Select columns($cols = '*', ?string $table = null)                                                      添加字段.
 * @method static \Leevel\Database\Ddd\Select setColumns($cols = '*', ?string $table = null)                                                   设置字段.
 * @method static \Leevel\Database\Ddd\Select where(...$cond)                                                                                  where 查询条件.
 * @method static \Leevel\Database\Ddd\Select orWhere(...$cond)                                                                                orWhere 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereRaw(string $raw)                                                                            Where 原生查询.
 * @method static \Leevel\Database\Ddd\Select orWhereRaw(string $raw)                                                                          Where 原生 OR 查询.
 * @method static \Leevel\Database\Ddd\Select whereExists($exists)                                                                             exists 方法支持
 * @method static \Leevel\Database\Ddd\Select whereNotExists($exists)                                                                          not exists 方法支持
 * @method static \Leevel\Database\Ddd\Select whereBetween(...$cond)                                                                           whereBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereNotBetween(...$cond)                                                                        whereNotBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereNull(...$cond)                                                                              whereNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereNotNull(...$cond)                                                                           whereNotNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereIn(...$cond)                                                                                whereIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereNotIn(...$cond)                                                                             whereNotIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereLike(...$cond)                                                                              whereLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereNotLike(...$cond)                                                                           whereNotLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereDate(...$cond)                                                                              whereDate 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereDay(...$cond)                                                                               whereDay 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereMonth(...$cond)                                                                             whereMonth 查询条件.
 * @method static \Leevel\Database\Ddd\Select whereYear(...$cond)                                                                              whereYear 查询条件.
 * @method static \Leevel\Database\Ddd\Select bind($names, $value = null, int $type = 2)                                                       参数绑定支持
 * @method static \Leevel\Database\Ddd\Select forceIndex($indexs, $type = 'FORCE')                                                             index 强制索引（或者忽略索引）.
 * @method static \Leevel\Database\Ddd\Select ignoreIndex($indexs)                                                                             index 忽略索引.
 * @method static \Leevel\Database\Ddd\Select join($table, $cols, ...$cond)                                                                    join 查询.
 * @method static \Leevel\Database\Ddd\Select innerJoin($table, $cols, ...$cond)                                                               innerJoin 查询.
 * @method static \Leevel\Database\Ddd\Select leftJoin($table, $cols, ...$cond)                                                                leftJoin 查询.
 * @method static \Leevel\Database\Ddd\Select rightJoin($table, $cols, ...$cond)                                                               rightJoin 查询.
 * @method static \Leevel\Database\Ddd\Select fullJoin($table, $cols, ...$cond)                                                                fullJoin 查询.
 * @method static \Leevel\Database\Ddd\Select crossJoin($table, $cols, ...$cond)                                                               crossJoin 查询.
 * @method static \Leevel\Database\Ddd\Select naturalJoin($table, $cols, ...$cond)                                                             naturalJoin 查询.
 * @method static \Leevel\Database\Ddd\Select union($selects, string $type = 'UNION')                                                          添加一个 UNION 查询.
 * @method static \Leevel\Database\Ddd\Select unionAll($selects)                                                                               添加一个 UNION ALL 查询.
 * @method static \Leevel\Database\Ddd\Select groupBy($expression)                                                                             指定 GROUP BY 子句.
 * @method static \Leevel\Database\Ddd\Select having(...$cond)                                                                                 添加一个 HAVING 条件 < 参数规范参考 where()方法 >.
 * @method static \Leevel\Database\Ddd\Select orHaving(...$cond)                                                                               orHaving 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingRaw(string $raw)                                                                           Having 原生查询.
 * @method static \Leevel\Database\Ddd\Select orHavingRaw(string $raw)                                                                         Having 原生 OR 查询.
 * @method static \Leevel\Database\Ddd\Select havingBetween(...$cond)                                                                          havingBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingNotBetween(...$cond)                                                                       havingNotBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingNull(...$cond)                                                                             havingNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingNotNull(...$cond)                                                                          havingNotNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingIn(...$cond)                                                                               havingIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingNotIn(...$cond)                                                                            havingNotIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingLike(...$cond)                                                                             havingLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingNotLike(...$cond)                                                                          havingNotLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingDate(...$cond)                                                                             havingDate 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingDay(...$cond)                                                                              havingDay 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingMonth(...$cond)                                                                            havingMonth 查询条件.
 * @method static \Leevel\Database\Ddd\Select havingYear(...$cond)                                                                             havingYear 查询条件.
 * @method static \Leevel\Database\Ddd\Select orderBy($expression, string $orderDefault = 'ASC')                                               添加排序.
 * @method static \Leevel\Database\Ddd\Select latest(string $field = 'create_at')                                                              最近排序数据.
 * @method static \Leevel\Database\Ddd\Select oldest(string $field = 'create_at')                                                              最早排序数据.
 * @method static \Leevel\Database\Ddd\Select distinct(bool $flag = true)                                                                      创建一个 SELECT DISTINCT 查询.
 * @method static \Leevel\Database\Ddd\Select count(string $field = '*', string $alias = 'row_count')                                          总记录数.
 * @method static \Leevel\Database\Ddd\Select avg(string $field, string $alias = 'avg_value')                                                  平均数.
 * @method static \Leevel\Database\Ddd\Select max(string $field, string $alias = 'max_value')                                                  最大值.
 * @method static \Leevel\Database\Ddd\Select min(string $field, string $alias = 'min_value')                                                  最小值.
 * @method static \Leevel\Database\Ddd\Select sum(string $field, string $alias = 'sum_value')                                                  合计
 * @method static \Leevel\Database\Ddd\Select one()                                                                                            指示仅查询第一个符合条件的记录.
 * @method static \Leevel\Database\Ddd\Select all()                                                                                            指示查询所有符合条件的记录.
 * @method static \Leevel\Database\Ddd\Select top(int $count = 30)                                                                             查询几条记录.
 * @method static \Leevel\Database\Ddd\Select limit(int $offset = 0, int $count = 0)                                                           limit 限制条数.
 * @method static \Leevel\Database\Ddd\Select forUpdate(bool $flag = true)                                                                     是否构造一个 FOR UPDATE 查询.
 * @method static \Leevel\Database\Ddd\Select setOption(string $name, $value)                                                                  设置查询参数.
 * @method static array getOption()                                                                                                            返回查询参数.
 * @method static array getBindParams()                                                                                                        返回参数绑定.
 */
class Select
{
    /**
     * 模型实体.
     *
     * @var \Leevel\Database\Ddd\IEntity
     */
    protected $entity;

    /**
     * 查询.
     *
     * @var \Leevel\Database\Select
     */
    protected $select;

    /**
     * 关联预载入.
     *
     * @var array
     */
    protected $preLoads = [];

    /**
     * 是否执行预载入查询.
     *
     * @var bool
     */
    protected static $preLoadsResult = true;

    /**
     * 构造函数.
     *
     * @param \Leevel\Database\Ddd\IEntity $entity
     * @param int                          $softDeletedType
     */
    public function __construct(IEntity $entity, int $softDeletedType = IEntity::WITHOUT_SOFT_DELETED)
    {
        $this->entity = $entity;
        $this->select = $this->entity->selectCollection($softDeletedType);
    }

    /**
     * call.
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        $result = $this->select->{$method}(...$args);

        return $this->normalizeSelectResult($result);
    }

    /**
     * 获取模型实体.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function entity(): IEntity
    {
        return $this->entity;
    }

    /**
     * 获取不执行预载入的查询结果.
     *
     * @param \Closure $preLoadsResult
     *
     * @return mixed
     */
    public static function withoutPreLoadsResult(Closure $preLoadsResult)
    {
        $old = static::$preLoadsResult;
        static::$preLoadsResult = false;
        $result = call_user_func($preLoadsResult);
        static::$preLoadsResult = $old;

        return $result;
    }

    /**
     * 添加预载入的关联.
     *
     * @param array $relation
     *
     * @return \Leevel\Database\Ddd\Select
     */
    public function eager(array $relation): self
    {
        $this->preLoads = array_merge(
            $this->preLoads,
            $this->parseWithRelation($relation)
        );

        return $this;
    }

    /**
     * 尝试解析结果预载.
     *
     * @param mixed $result
     *
     * @return mixed
     */
    public function preLoadResult($result)
    {
        list($result, $type) = $this->conversionToEntitys($result);

        if ($type) {
            $result = $this->preLoadRelation($result);

            if ('entity' === $type) {
                $result = reset($result);
            } elseif ('collection' === $type) {
                $result = new Collection($result);
            }
        }

        return $result;
    }

    /**
     * 通过主键查找模型实体.
     *
     * @param int   $id
     * @param array $column
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function findEntity(int $id, array $column = ['*']): IEntity
    {
        $result = $this->select
            ->where($this->entity->singlePrimaryKey(), '=', $id)
            ->setColumns($column)
            ->findOne();

        return $this->normalizeSelectResult($result);
    }

    /**
     * 根据主键查找模型实体.
     *
     * @param array $ids
     * @param array $column
     *
     * @return \Leevel\Collection\Collection
     */
    public function findMany(array $ids, array $column = ['*']): Collection
    {
        if (empty($ids)) {
            return $this->entity->collection();
        }

        $result = $this->select
            ->whereIn($this->entity->singlePrimaryKey(), $ids)
            ->setColumns($column)
            ->findAll();

        return $this->normalizeSelectResult($result);
    }

    /**
     * 通过主键查找模型实体，未找到则抛出异常.
     *
     * @param int   $id
     * @param array $column
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function findOrFail(int $id, array $column = ['*']): IEntity
    {
        $result = $this->findEntity($id, $column);
        if (null !== $result->prop($this->entity->singlePrimaryKey())) {
            return $result;
        }

        throw (new EntityNotFoundException())
            ->setEntity(get_class($this->entity));
    }

    /**
     * 预载入模型实体.
     *
     * @param \Leevel\Database\Ddd\IEntity[] $entitys
     *
     * @return array
     */
    protected function preLoadRelation(array $entitys): array
    {
        foreach ($this->preLoads as $name => $condition) {
            if (false === strpos($name, '.')) {
                $entitys = $this->loadRelation($entitys, $name, $condition);
            }
        }

        return $entitys;
    }

    /**
     * 取得关联模型实体.
     *
     * @param string $name
     *
     * @return \Leevel\Database\Ddd\Relation\Relation
     */
    protected function getRelation(string $name): Relation
    {
        $relation = Relation::withoutRelationCondition(function () use ($name): Relation {
            return $this->entity->loadRelation($name);
        });

        $nested = $this->nestedRelation($name);
        if (count($nested) > 0) {
            $relation->getSelect()->eager($nested);
        }

        return $relation;
    }

    /**
     * 尝试取得嵌套关联.
     *
     * @param string $relation
     *
     * @return array
     */
    protected function nestedRelation(string $relation): array
    {
        $nested = [];
        foreach ($this->preLoads as $name => $condition) {
            if ($this->isNested($name, $relation)) {
                $nested[substr($name, strlen($relation.'.'))] = $condition;
            }
        }

        return $nested;
    }

    /**
     * 判断是否存在嵌套关联.
     *
     * @param string $name
     * @param string $relation
     *
     * @return bool
     */
    protected function isNested(string $name, string $relation): bool
    {
        return contains($name, '.') && starts_with($name, $relation.'.');
    }

    /**
     * 格式化预载入关联.
     *
     * @param array $relation
     *
     * @return array
     */
    protected function parseWithRelation(array $relation): array
    {
        $data = [];
        foreach ($relation as $name => $condition) {
            if (is_int($name)) {
                list($name, $condition) = [$condition, null];
            }

            $data = $this->parseNestedWith($name, $data);
            $data[$name] = $condition;
        }

        return $data;
    }

    /**
     * 解析嵌套关联.
     *
     * @param string $name
     * @param array  $result
     *
     * @return array
     */
    protected function parseNestedWith(string $name, array $result): array
    {
        $progress = [];
        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;
            if (!isset($result[$last = implode('.', $progress)])) {
                $result[$last] = null;
            }
        }

        return $result;
    }

    /**
     * 转换结果到模型实体类型.
     *
     * @param mixed $result
     *
     * @return array
     */
    protected function conversionToEntitys($result): array
    {
        $type = '';
        if ($result instanceof Collection) {
            $data = [];
            foreach ($result as $entity) {
                $data[] = $entity;
            }
            $result = $data;
            $type = 'collection';
        } elseif (is_object($result) && $result instanceof IEntity) {
            $result = [$result];
            $type = 'entity';
        }

        return [$result, $type];
    }

    /**
     * 关联数据设置到模型实体上.
     *
     * @param \Leevel\Database\Ddd\IEntity[] $entitys
     * @param string                         $name
     * @param null|\Closure                  $condition
     *
     * @return array
     */
    protected function loadRelation(array $entitys, string $name, ?Closure $condition = null): array
    {
        $relation = $this->getRelation($name);
        $relation->preLoadCondition($entitys);
        if ($condition) {
            call_user_func($condition, $relation);
        }

        return $relation->matchPreLoad($entitys, $relation->getPreLoad(), $name);
    }

    /**
     * 整理查询结果.
     *
     * @param mixed $result
     *
     * @return mixed
     */
    protected function normalizeSelectResult($result)
    {
        if ($result instanceof DatabaseSelect) {
            return $this;
        }

        if (false === static::$preLoadsResult || !$this->preLoads) {
            return $result;
        }

        if (is_array($result)) {
            if (isset($result[DatabaseSelect::PAGE]) &&
                true === $result[DatabaseSelect::PAGE]) {
                $result[1] = $this->preLoadResult($result[1]);
            }

            return $result;
        }

        return $this->preLoadResult($result);
    }
}

// import fn.
class_exists(normalize::class);
class_exists(contains::class);
class_exists(starts_with::class);
