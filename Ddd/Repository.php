<?php

declare(strict_types=1);

namespace Leevel\Database\Ddd;

use Leevel\Database\Page;
use Leevel\Event\IDispatch;
use Leevel\Support\Collection;

/**
 * 仓储.
 *
 * @method static \Leevel\Database\Ddd\Select                                            eager(array $relation)                                                                                                                                             添加预载入关联查询.
 * @method static mixed                                                                  preLoadResult(mixed $result)                                                                                                                                       尝试解析结果预载.
 * @method static \Leevel\Database\Ddd\Entity                                            findEntity(null|array|\Closure|int|string $idOrCondition = null, array $column = [])                                                                               通过主键或条件查找实体.
 * @method static \Leevel\Database\Ddd\Collection                                        findMany(null|array|\Closure $idsOrCondition = null, array $column = [])                                                                                           通过主键或条件查找多个实体.
 * @method static \Leevel\Database\Ddd\Entity                                            findOrFail(null|array|\Closure|int|string $idOrCondition = null, array $column = [])                                                                               通过主键或条件查找实体，未找到则抛出异常.
 * @method static \Leevel\Database\Ddd\Select                                            withSoftDeleted()                                                                                                                                                  包含软删除数据的实体查询对象.
 * @method static \Leevel\Database\Ddd\Select                                            onlySoftDeleted()
 * @method static void                                                                   setCache(?\Leevel\Cache\Manager $cache)                                                                                                                            设置缓存.
 * @method static ?\Leevel\Cache\Manager                                                 getCache()                                                                                                                                                         获取缓存.
 * @method static \Leevel\Database\Ddd\Select                                            databaseSelect()                                                                                                                                                   返回查询对象.
 * @method static ?\PDO                                                                  pdo(bool|int $master = false)                                                                                                                                      返回 PDO 查询连接.
 * @method static mixed                                                                  query(string $sql, array $bindParams = [], bool|int $master = false, ?string $cacheName = null, ?int $cacheExpire = null, ?\Leevel\Cache\ICache $cache = null)     查询数据记录.
 * @method static array                                                                  procedure(string $sql, array $bindParams = [], bool|int $master = false, ?string $cacheName = null, ?int $cacheExpire = null, ?\Leevel\Cache\ICache $cache = null) 查询存储过程数据记录.
 * @method static int|string                                                             execute(string $sql, array $bindParams = [])                                                                                                                       执行 SQL 语句.
 * @method static \Generator                                                             cursor(string $sql, array $bindParams = [], bool|int $master = false)                                                                                              游标查询.
 * @method static \PDOStatement                                                          prepare(string $sql, array $bindParams = [], bool|int $master = false)                                                                                             SQL 预处理.
 * @method static mixed                                                                  transaction(\Closure $action)                                                                                                                                      执行数据库事务.
 * @method static void                                                                   beginTransaction()                                                                                                                                                 启动事务.
 * @method static bool                                                                   inTransaction()                                                                                                                                                    检查是否处于事务中.
 * @method static void                                                                   commit()                                                                                                                                                           用于非自动提交状态下面的查询提交.
 * @method static void                                                                   rollBack()                                                                                                                                                         事务回滚.
 * @method static string                                                                 lastInsertId(?string $name = null)                                                                                                                                 获取最后插入 ID 或者列.
 * @method static string                                                                 getLastSql()                                                                                                                                                       获取最近一次查询的 SQL 语句.
 * @method static void                                                                   setRealLastSql(array $realLastSql)                                                                                                                                 设置最近一次真实查询的 SQL 语句.
 * @method static array                                                                  getRealLastSql()                                                                                                                                                   获取最近一次真实查询的 SQL 语句.
 * @method static int                                                                    numRows()                                                                                                                                                          返回影响记录.
 * @method static void                                                                   close()                                                                                                                                                            关闭数据库.
 * @method static void                                                                   freePDOStatement()                                                                                                                                                 释放 PDO 预处理查询.
 * @method static void                                                                   closeConnects()                                                                                                                                                    关闭数据库连接.
 * @method static string                                                                 getRawSql(string $sql, array $bindParams)                                                                                                                          从 PDO 预处理语句中获取原始 SQL 查询字符串.
 * @method static string                                                                 parseDsn(array $config)                                                                                                                                            DSN 解析.
 * @method static array                                                                  getTableNames(string $dbName, bool|int $master = false)                                                                                                            取得数据库表名列表.
 * @method static array                                                                  getTableColumns(string $tableName, bool|int $master = false)                                                                                                       取得数据库表字段信息.
 * @method static string                                                                 identifierColumn(string $name)                                                                                                                                     SQL 字段格式化.
 * @method static string                                                                 limitCount(?int $limitCount = null, ?int $limitOffset = null)                                                                                                      分析查询条数.
 * @method static \Leevel\Database\Condition                                             databaseCondition()                                                                                                                                                查询对象.
 * @method static \Leevel\Database\IDatabase                                             databaseConnect()                                                                                                                                                  返回数据库连接对象.
 * @method static \Leevel\Database\Ddd\Select                                            master(bool|int $master = false)                                                                                                                                   设置是否查询主服务器.
 * @method static \Leevel\Database\Ddd\Select                                            asSome(?\Closure $asSome = null, array $args = [])                                                                                                                 设置以某种包装返会结果.
 * @method static \Leevel\Database\Ddd\Select                                            asArray(?\Closure $asArray = null)                                                                                                                                 设置返会结果为数组.
 * @method static \Leevel\Database\Ddd\Select                                            asCollection(bool $asCollection = true, array $valueTypes = [])                                                                                                    设置是否以集合返回.
 * @method static int|string                                                             insert(array|string $data, array $bind = [], array|bool $replace = false)                                                                                          插入数据 insert (支持原生 SQL).
 * @method static int                                                                    update(array|string $data, array $bind = [])                                                                                                                       更新数据 update (支持原生 SQL).
 * @method static int                                                                    updateColumn(string $column, mixed $value, array $bind = [])                                                                                                       更新某个字段的值
 * @method static array|int                                                              updateIncrease(string $column, int $step = 1, array $bind = [])                                                                                                    字段递增.
 * @method static int                                                                    updateDecrease(string $column, int $step = 1, array $bind = [])                                                                                                    字段减少.
 * @method static int                                                                    delete(?string $data = null, array $bind = [])                                                                                                                     删除数据 delete (支持原生 SQL).
 * @method static array|int                                                              truncate()                                                                                                                                                         清空表重置自增 ID.
 * @method static mixed                                                                  findOne()                                                                                                                                                          返回一条记录.
 * @method static \Leevel\Database\Ddd\EntityCollection|\Leevel\Support\Collection|array find(?int $num = null)                                                                                                                                             返回最后几条记录.
 * @method static mixed                                                                  value(string $field)                                                                                                                                               返回一个字段的值
 * @method static array                                                                  list(mixed $fieldValue, ?string $fieldKey = null)                                                                                                                  返回一列数据.
 * @method static void                                                                   chunk(int $count, \Closure $chunk)                                                                                                                                 数据分块处理.
 * @method static void                                                                   each(int $count, \Closure $each)                                                                                                                                   数据分块处理依次回调.
 * @method static mixed                                                                  findAvg(string $field, string $alias = 'avg_value')                                                                                                                平均数.
 * @method static mixed                                                                  findMax(string $field, string $alias = 'max_value')                                                                                                                最大值.
 * @method static mixed                                                                  findMin(string $field, string $alias = 'min_value')                                                                                                                最小值.
 * @method static mixed                                                                  findSum(string $field, string $alias = 'sum_value')                                                                                                                合计.
 * @method static \Leevel\Database\Page                                                  page(int $currentPage, int $perPage = 10, string $column = '*', array $config = [])                                                                                分页查询.
 * @method static \Leevel\Database\Page                                                  pageMacro(int $currentPage, int $perPage = 10, array $config = [])                                                                                                 创建一个无限数据的分页查询.
 * @method static \Leevel\Database\Page                                                  pagePrevNext(int $currentPage, int $perPage = 10, array $config = [])                                                                                              创建一个只有上下页的分页查询.
 * @method static int                                                                    pageCount(string $cols = '*')                                                                                                                                      取得分页查询记录数量.
 * @method static string                                                                 makeSql(bool $withLogicGroup = false)                                                                                                                              获得查询字符串.
 * @method static \Leevel\Database\Ddd\Select                                            cache(string $name, ?int $expire = null, ?\Leevel\Cache\ICache $cache = null)                                                                                      设置查询缓存.
 * @method static \Leevel\Database\Ddd\Select                                            forPage(int $page, int $perPage = 10)                                                                                                                              根据分页设置条件.
 * @method static \Leevel\Database\Ddd\Select                                            time(string $type = 'date')                                                                                                                                        时间控制语句开始.
 * @method static \Leevel\Database\Ddd\Select                                            endTime()                                                                                                                                                          时间控制语句结束.
 * @method static \Leevel\Database\Ddd\Select                                            reset(?string $config = null)                                                                                                                                      重置查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            comment(string $comment)                                                                                                                                           查询注释.
 * @method static \Leevel\Database\Ddd\Select                                            prefix(string $prefix)                                                                                                                                             prefix 查询.
 * @method static \Leevel\Database\Ddd\Select                                            table(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols = '*')                                                   添加一个要查询的表及其要查询的字段.
 * @method static string                                                                 getAlias()                                                                                                                                                         获取表别名.
 * @method static \Leevel\Database\Ddd\Select                                            columns(array|string $cols = '*', ?string $table = null)                                                                                                           添加字段.
 * @method static \Leevel\Database\Ddd\Select                                            setColumns(array|string $cols = '*', ?string $table = null)                                                                                                        设置字段.
 * @method static \Leevel\Database\Ddd\Select                                            field(array|string $cols = '*', ?string $table = null)                                                                                                             设置字段别名方法.
 * @method static string                                                                 raw(string $raw)                                                                                                                                                   原生查询.
 * @method static \Leevel\Database\Ddd\Select                                            middlewares(string ...$middlewares)                                                                                                                                查询中间件.
 * @method static array                                                                  registerMiddlewares(array $middlewares, bool $force = false)                                                                                                       注册查询中间件.
 * @method static \Leevel\Database\Ddd\Select                                            where(...$cond)                                                                                                                                                    where 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            orWhere(...$cond)                                                                                                                                                  orWhere 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereRaw(string $raw)                                                                                                                                              Where 原生查询.
 * @method static \Leevel\Database\Ddd\Select                                            orWhereRaw(string $raw)                                                                                                                                            Where 原生 OR 查询.
 * @method static \Leevel\Database\Ddd\Select                                            whereExists($exists)                                                                                                                                               exists 方法支持
 * @method static \Leevel\Database\Ddd\Select                                            whereNotExists($exists)                                                                                                                                            not exists 方法支持
 * @method static \Leevel\Database\Ddd\Select                                            whereBetween(...$cond)                                                                                                                                             whereBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereNotBetween(...$cond)                                                                                                                                          whereNotBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereNull(...$cond)                                                                                                                                                whereNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereNotNull(...$cond)                                                                                                                                             whereNotNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereIn(...$cond)                                                                                                                                                  whereIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereNotIn(...$cond)                                                                                                                                               whereNotIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereLike(...$cond)                                                                                                                                                whereLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereNotLike(...$cond)                                                                                                                                             whereNotLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereDate(...$cond)                                                                                                                                                whereDate 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereDay(...$cond)                                                                                                                                                 whereDay 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereMonth(...$cond)                                                                                                                                               whereMonth 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            whereYear(...$cond)                                                                                                                                                whereYear 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            bind(mixed $names, mixed $value = null, ?int $dataType = null)                                                                                                     参数绑定支持.
 * @method static \Leevel\Database\Ddd\Select                                            forceIndex(array|string $indexs, string $type = 'FORCE')                                                                                                           index 强制索引（或者忽略索引）.
 * @method static \Leevel\Database\Ddd\Select                                            ignoreIndex(array|string $indexs)                                                                                                                                  index 忽略索引.
 * @method static \Leevel\Database\Ddd\Select                                            join(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                                join 查询.
 * @method static \Leevel\Database\Ddd\Select                                            innerJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                           innerJoin 查询.
 * @method static \Leevel\Database\Ddd\Select                                            leftJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                            leftJoin 查询.
 * @method static \Leevel\Database\Ddd\Select                                            rightJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                           rightJoin 查询.
 * @method static \Leevel\Database\Ddd\Select                                            fullJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                            fullJoin 查询.
 * @method static \Leevel\Database\Ddd\Select                                            crossJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                           crossJoin 查询.
 * @method static \Leevel\Database\Ddd\Select                                            naturalJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)                                         naturalJoin 查询.
 * @method static \Leevel\Database\Ddd\Select                                            union(array|callable|\Leevel\Database\Condition|\Leevel\Database\Select|string $selects, string $type = 'UNION')                                                   添加一个 UNION 查询.
 * @method static \Leevel\Database\Ddd\Select                                            unionAll(array|callable|\Leevel\Database\Condition|\Leevel\Database\Select|string $selects)                                                                        添加一个 UNION ALL 查询.
 * @method static \Leevel\Database\Ddd\Select                                            groupBy(array|string $expression)                                                                                                                                  指定 GROUP BY 子句.
 * @method static \Leevel\Database\Ddd\Select                                            having(...$cond)                                                                                                                                                   添加一个 HAVING 条件.
 * @method static \Leevel\Database\Ddd\Select                                            orHaving(...$cond)                                                                                                                                                 orHaving 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingRaw(string $raw)                                                                                                                                             having 原生查询.
 * @method static \Leevel\Database\Ddd\Select                                            orHavingRaw(string $raw)                                                                                                                                           having 原生 OR 查询.
 * @method static \Leevel\Database\Ddd\Select                                            havingBetween(...$cond)                                                                                                                                            havingBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingNotBetween(...$cond)                                                                                                                                         havingNotBetween 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingNull(...$cond)                                                                                                                                               havingNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingNotNull(...$cond)                                                                                                                                            havingNotNull 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingIn(...$cond)                                                                                                                                                 havingIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingNotIn(...$cond)                                                                                                                                              havingNotIn 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingLike(...$cond)                                                                                                                                               havingLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingNotLike(...$cond)                                                                                                                                            havingNotLike 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingDate(...$cond)                                                                                                                                               havingDate 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingDay(...$cond)                                                                                                                                                havingDay 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingMonth(...$cond)                                                                                                                                              havingMonth 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            havingYear(...$cond)                                                                                                                                               havingYear 查询条件.
 * @method static \Leevel\Database\Ddd\Select                                            orderBy(array|string $expression, string $orderDefault = 'ASC')                                                                                                    添加排序.
 * @method static \Leevel\Database\Ddd\Select                                            latest(string $field = 'create_at')                                                                                                                                最近排序数据.
 * @method static \Leevel\Database\Ddd\Select                                            oldest(string $field = 'create_at')                                                                                                                                最早排序数据.
 * @method static \Leevel\Database\Ddd\Select                                            distinct(bool $flag = true)                                                                                                                                        创建一个 SELECT DISTINCT 查询.
 * @method static \Leevel\Database\Ddd\Select                                            count(string $field = '*', string $alias = 'row_count')                                                                                                            总记录数.
 * @method static \Leevel\Database\Ddd\Select                                            avg(string $field, string $alias = 'avg_value')                                                                                                                    平均数.
 * @method static \Leevel\Database\Ddd\Select                                            max(string $field, string $alias = 'max_value')                                                                                                                    最大值.
 * @method static \Leevel\Database\Ddd\Select                                            min(string $field, string $alias = 'min_value')                                                                                                                    最小值.
 * @method static \Leevel\Database\Ddd\Select                                            sum(string $field, string $alias = 'sum_value')                                                                                                                    合计
 * @method static \Leevel\Database\Ddd\Select                                            one()                                                                                                                                                              指示仅查询第一个符合条件的记录.
 * @method static \Leevel\Database\Ddd\Select                                            all()                                                                                                                                                              指示查询所有符合条件的记录.
 * @method static \Leevel\Database\Ddd\Select                                            top(int $count = 30)                                                                                                                                               查询几条记录.
 * @method static \Leevel\Database\Ddd\Select                                            limit(int $offset = 0, int $count = 0)                                                                                                                             limit 限制条数.
 * @method static \Leevel\Database\Ddd\Select                                            forUpdate(bool $flag = true)                                                                                                                                       排它锁 FOR UPDATE 查询.
 * @method static \Leevel\Database\Ddd\Select                                            lockShare(bool $flag = true)                                                                                                                                       共享锁 LOCK SHARE 查询.
 * @method static array                                                                  getBindParams()                                                                                                                                                    返回参数绑定.
 * @method static void                                                                   resetBindParams(array $bindParams = [])                                                                                                                            重置参数绑定.
 * @method static void                                                                   setBindParamsPrefix(string $bindParamsPrefix)                                                                                                                      设置参数绑定前缀.
 * @method static \Leevel\Database\Ddd\Select                                            if(mixed $value = false)                                                                                                                                           条件语句 if.
 * @method static \Leevel\Database\Ddd\Select                                            elif(mixed $value = false)                                                                                                                                         条件语句 elif.
 * @method static \Leevel\Database\Ddd\Select                                            else()                                                                                                                                                             条件语句 else.
 * @method static \Leevel\Database\Ddd\Select                                            fi()                                                                                                                                                               条件语句 fi.
 * @method static \Leevel\Database\Ddd\Select                                            setFlowControl(bool $inFlowControl, bool $isFlowControlTrue)                                                                                                       设置当前条件表达式状态.
 * @method static bool                                                                   checkFlowControl()                                                                                                                                                 验证一下条件表达式是否通过.
 */
class Repository
{
    /**
     * 批量插入数据事件.
     */
    public const INSERT_ALL_EVENT = 'database.repository.insertall';

    /**
     * 批量插入回调.
     */
    protected ?\Closure $insertAllBoot = null;

    /**
     * 构造函数.
     */
    public function __construct(protected Entity $entity, protected ?IDispatch $dispatch = null)
    {
    }

    /**
     * 实现魔术方法 __call.
     */
    public function __call(string $method, array $args): mixed
    {
        return $this->select()->{$method}(...$args);
    }

    /**
     * 批量插入回调.
     */
    public function insertAllBoot(\Closure $boot): void
    {
        $this->insertAllBoot = $boot;
    }

    /**
     * 批量插入数据 insertAll.
     */
    public function insertAll(array $data, array $bind = [], bool|array $replace = false): int|string
    {
        if ($this->dispatch) {
            $this->dispatch->handle(
                self::INSERT_ALL_EVENT,
                $this,
            );
        }

        if ($this->insertAllBoot) {
            $insertAllBoot = $this->insertAllBoot;
            $insertAllBoot($data, $bind, $replace);
        }

        return $this->select()->insertAll($data, $bind, $replace);
    }

    /**
     * 取得所有记录.
     */
    public function findAll(?\Closure $condition = null): EntityCollection|Collection|array
    {
        $select = $this->condition($condition);

        // @phpstan-ignore-next-line
        return $select->findAll();
    }

    /**
     * 返回一列数据.
     */
    public function findList(?\Closure $condition, mixed $fieldValue, ?string $fieldKey = null): array
    {
        $select = $this->condition($condition);

        return $select->list($fieldValue, $fieldKey);
    }

    /**
     * 取得记录数量.
     */
    public function findCount(?\Closure $condition = null, string $field = '*'): int
    {
        $select = $this->condition($condition);

        return $select->findCount($field);
    }

    /**
     * 分页查询.
     *
     * - 可以渲染 HTML.
     */
    public function findPage(int $currentPage, int $perPage = 10, ?\Closure $condition = null, string $column = '*', array $config = []): Page
    {
        $select = $this->condition($condition);

        return $select->page($currentPage, $perPage, $column, $config);
    }

    /**
     * 创建一个无限数据的分页查询.
     */
    public function findPageMacro(int $currentPage, int $perPage = 10, ?\Closure $condition = null, array $config = []): Page
    {
        $select = $this->condition($condition);

        return $select->pageMacro($currentPage, $perPage, $config);
    }

    /**
     * 创建一个只有上下页的分页查询.
     */
    public function findPagePrevNext(int $currentPage, int $perPage = 10, ?\Closure $condition = null, array $config = []): Page
    {
        $select = $this->condition($condition);

        return $select->pagePrevNext($currentPage, $perPage, $config);
    }

    /**
     * 判断指定数组数据值是否存在.
     *
     * @throws \Leevel\Database\Ddd\DataNotFoundException
     */
    public function validateDataExists(array $data, ?string $field = null, ?\Closure $condition = null, string $countField = '*'): void
    {
        if (!$field) {
            $field = $this->entity->singlePrimaryKey();
        }
        $data = array_values(array_unique($data));
        $select = $this->condition();
        $select->whereIn($field, $data);

        if ($condition) {
            $this->normalizeCondition($condition, $select);
        }

        $count = $select->findCount($countField);
        if ($count === \count($data)) {
            return;
        }

        throw new DataNotFoundException(sprintf('Data of `%s` was not found.', \get_class($this->entity)));
    }

    /**
     * 条件查询器.
     */
    public function condition(?\Closure $condition = null): Select
    {
        $select = $this
            ->select()
            ->databaseSelect()
        ;

        if ($condition) {
            $this->normalizeCondition($condition, $select);
        }

        return $select;
    }

    /**
     * 返回基础查询.
     */
    public function select(): Select
    {
        return $this->entity->select();
    }

    /**
     * 新增实体.
     */
    public function createEntity(Entity $entity): mixed
    {
        return $entity->create()->flush();
    }

    /**
     * 更新实体.
     */
    public function updateEntity(Entity $entity): mixed
    {
        return $entity->update()->flush();
    }

    /**
     * 替换实体.
     */
    public function replaceEntity(Entity $entity): mixed
    {
        return $entity->replace()->flush();
    }

    /**
     * 响应删除.
     */
    public function deleteEntity(Entity $entity, bool $forceDelete = false): mixed
    {
        return $entity->delete($forceDelete)->flush();
    }

    /**
     * 强制删除实体.
     */
    public function forceDeleteEntity(Entity $entity): mixed
    {
        return $entity->delete(true)->flush();
    }

    /**
     * 从数据库重新读取当前对象的属性.
     */
    public function refreshEntity(Entity $entity): void
    {
        $entity->refresh();
    }

    /**
     * 返回实体.
     */
    public function entity(): Entity
    {
        return $this->entity;
    }

    /**
     * 处理查询条件.
     */
    protected function normalizeCondition(\Closure $condition, Select $select): void
    {
        $condition($select, $this->entity);
    }
}
