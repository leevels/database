<?php

declare(strict_types=1);

namespace Leevel\Database;

use Leevel\Cache\ICache;
use Leevel\Server\Pool\IConnection;

/**
 * 数据库接口.
 *
 * @method static \Leevel\Database\Condition                                             databaseCondition()                                                                                                        查询对象.
 * @method static \Leevel\Database\IDatabase                                             databaseConnect()                                                                                                          返回数据库连接对象.
 * @method static \Leevel\Database\Select                                                master(bool|int $master = false)                                                                                           设置是否查询主服务器.
 * @method static \Leevel\Database\Select                                                asSome(?\Closure $asSome = null, array $args = [])                                                                         设置以某种包装返会结果.
 * @method static \Leevel\Database\Select                                                asArray(?\Closure $asArray = null)                                                                                         设置返会结果为数组.
 * @method static \Leevel\Database\Select                                                asCollection(bool $asCollection = true, array $valueTypes = [])                                                            设置是否以集合返回.
 * @method static mixed                                                                  select(null|callable|\Leevel\Database\Select|string $data = null, array $bind = [])                                        原生 SQL 查询数据.
 * @method static int|string                                                             insert(array|string $data, array $bind = [], array|bool $replace = false)                                                  插入数据 insert (支持原生 SQL).
 * @method static int|string                                                             insertAll(array $data, array $bind = [], array|bool $replace = false)                                                      批量插入数据 insertAll.
 * @method static int                                                                    update(array|string $data, array $bind = [])                                                                               更新数据 update (支持原生 SQL).
 * @method static int                                                                    updateColumn(string $column, mixed $value, array $bind = [])                                                               更新某个字段的值
 * @method static int                                                                    updateIncrease(string $column, int $step = 1, array $bind = [])                                                            字段递增.
 * @method static int                                                                    updateDecrease(string $column, int $step = 1, array $bind = [])                                                            字段减少.
 * @method static int                                                                    delete(?string $data = null, array $bind = [])                                                                             删除数据 delete (支持原生 SQL).
 * @method static array|int                                                              truncate()                                                                                                                 清空表重置自增 ID.
 * @method static mixed                                                                  findOne()                                                                                                                  返回一条记录.
 * @method static \Leevel\Database\Ddd\EntityCollection|\Leevel\Support\Collection|array findAll()                                                                                                                  返回所有记录.
 * @method static array                                                                  findArray()                                                                                                                以数组返回所有记录.
 * @method static array                                                                  findAsArray()                                                                                                              以数组返回所有记录（每一项也为数组）.
 * @method static \Leevel\Database\Ddd\EntityCollection|\Leevel\Support\Collection       findCollection()                                                                                                           以集合返回所有记录.
 * @method static \Leevel\Database\Ddd\EntityCollection|\Leevel\Support\Collection|array find(?int $num = null)                                                                                                     返回最后几条记录.
 * @method static mixed                                                                  value(string $field)                                                                                                       返回一个字段的值
 * @method static array                                                                  list(mixed $fieldValue, ?string $fieldKey = null)                                                                          返回一列数据.
 * @method static void                                                                   chunk(int $count, \Closure $chunk)                                                                                         数据分块处理.
 * @method static void                                                                   each(int $count, \Closure $each)                                                                                           数据分块处理依次回调.
 * @method static int                                                                    findCount(string $field = '*', string $alias = 'row_count')                                                                总记录数.
 * @method static mixed                                                                  findAvg(string $field, string $alias = 'avg_value')                                                                        平均数.
 * @method static mixed                                                                  findMax(string $field, string $alias = 'max_value')                                                                        最大值.
 * @method static mixed                                                                  findMin(string $field, string $alias = 'min_value')                                                                        最小值.
 * @method static mixed                                                                  findSum(string $field, string $alias = 'sum_value')                                                                        合计.
 * @method static \Leevel\Database\Page                                                  page(int $currentPage, int $perPage = 10, string $column = '*', array $config = [])                                        分页查询.
 * @method static \Leevel\Database\Page                                                  pageMacro(int $currentPage, int $perPage = 10, array $config = [])                                                         创建一个无限数据的分页查询.
 * @method static \Leevel\Database\Page                                                  pagePrevNext(int $currentPage, int $perPage = 10, array $config = [])                                                      创建一个只有上下页的分页查询.
 * @method static int                                                                    pageCount(string $cols = '*')                                                                                              取得分页查询记录数量.
 * @method static string                                                                 makeSql(bool $withLogicGroup = false)                                                                                      获得查询字符串.
 * @method static \Leevel\Database\Select                                                cache(string $name, ?int $expire = null, ?\Leevel\Cache\ICache $cache = null)                                              设置查询缓存.
 * @method static \Leevel\Database\Select                                                forPage(int $page, int $perPage = 10)                                                                                      根据分页设置条件.
 * @method static \Leevel\Database\Select                                                time(string $type = 'date')                                                                                                时间控制语句开始.
 * @method static \Leevel\Database\Select                                                endTime()                                                                                                                  时间控制语句结束.
 * @method static \Leevel\Database\Select                                                reset(?string $config = null)                                                                                              重置查询条件.
 * @method static \Leevel\Database\Select                                                comment(string $comment)                                                                                                   查询注释.
 * @method static \Leevel\Database\Select                                                prefix(string $prefix)                                                                                                     prefix 查询.
 * @method static \Leevel\Database\Select                                                table(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols = '*')           添加一个要查询的表及其要查询的字段.
 * @method static string                                                                 getAlias()                                                                                                                 获取表别名.
 * @method static \Leevel\Database\Select                                                columns(array|string $cols = '*', ?string $table = null)                                                                   添加字段.
 * @method static \Leevel\Database\Select                                                setColumns(array|string $cols = '*', ?string $table = null)                                                                设置字段.
 * @method static \Leevel\Database\Select                                                field(array|string $cols = '*', ?string $table = null)                                                                     设置字段别名方法.
 * @method static string                                                                 raw(string $raw)                                                                                                           原生查询.
 * @method static \Leevel\Database\Select                                                middlewares(string ...$middlewares)                                                                                        查询中间件.
 * @method static array                                                                  registerMiddlewares(array $middlewares, bool $force = false)                                                               注册查询中间件.
 * @method static \Leevel\Database\Select                                                where(...$cond)                                                                                                            where 查询条件.
 * @method static \Leevel\Database\Select                                                orWhere(...$cond)                                                                                                          orWhere 查询条件.
 * @method static \Leevel\Database\Select                                                whereRaw(string $raw)                                                                                                      Where 原生查询.
 * @method static \Leevel\Database\Select                                                orWhereRaw(string $raw)                                                                                                    Where 原生 OR 查询.
 * @method static \Leevel\Database\Select                                                whereExists($exists)                                                                                                       exists 方法支持
 * @method static \Leevel\Database\Select                                                whereNotExists($exists)                                                                                                    not exists 方法支持
 * @method static \Leevel\Database\Select                                                whereBetween(...$cond)                                                                                                     whereBetween 查询条件.
 * @method static \Leevel\Database\Select                                                whereNotBetween(...$cond)                                                                                                  whereNotBetween 查询条件.
 * @method static \Leevel\Database\Select                                                whereNull(...$cond)                                                                                                        whereNull 查询条件.
 * @method static \Leevel\Database\Select                                                whereNotNull(...$cond)                                                                                                     whereNotNull 查询条件.
 * @method static \Leevel\Database\Select                                                whereIn(...$cond)                                                                                                          whereIn 查询条件.
 * @method static \Leevel\Database\Select                                                whereNotIn(...$cond)                                                                                                       whereNotIn 查询条件.
 * @method static \Leevel\Database\Select                                                whereLike(...$cond)                                                                                                        whereLike 查询条件.
 * @method static \Leevel\Database\Select                                                whereNotLike(...$cond)                                                                                                     whereNotLike 查询条件.
 * @method static \Leevel\Database\Select                                                whereDate(...$cond)                                                                                                        whereDate 查询条件.
 * @method static \Leevel\Database\Select                                                whereDay(...$cond)                                                                                                         whereDay 查询条件.
 * @method static \Leevel\Database\Select                                                whereMonth(...$cond)                                                                                                       whereMonth 查询条件.
 * @method static \Leevel\Database\Select                                                whereYear(...$cond)                                                                                                        whereYear 查询条件.
 * @method static \Leevel\Database\Select                                                bind(mixed $names, mixed $value = null, ?int $dataType = null)                                                             参数绑定支持.
 * @method static \Leevel\Database\Select                                                forceIndex(array|string $indexs, string $type = 'FORCE')                                                                   index 强制索引（或者忽略索引）.
 * @method static \Leevel\Database\Select                                                ignoreIndex(array|string $indexs)                                                                                          index 忽略索引.
 * @method static \Leevel\Database\Select                                                join(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)        join 查询.
 * @method static \Leevel\Database\Select                                                innerJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)   innerJoin 查询.
 * @method static \Leevel\Database\Select                                                leftJoin($table, array|string $cols, ...$cond)                                                                             leftJoin 查询.
 * @method static \Leevel\Database\Select                                                rightJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)   rightJoin 查询.
 * @method static \Leevel\Database\Select                                                fullJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)    fullJoin 查询.
 * @method static \Leevel\Database\Select                                                crossJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond)   crossJoin 查询.
 * @method static \Leevel\Database\Select                                                naturalJoin(array|\Closure|\Leevel\Database\Condition|\Leevel\Database\Select|string $table, array|string $cols, ...$cond) naturalJoin 查询.
 * @method static \Leevel\Database\Select                                                union(array|callable|\Leevel\Database\Condition|\Leevel\Database\Select|string $selects, string $type = 'UNION')           添加一个 UNION 查询.
 * @method static \Leevel\Database\Select                                                unionAll(array|callable|\Leevel\Database\Condition|\Leevel\Database\Select|string $selects)                                添加一个 UNION ALL 查询.
 * @method static \Leevel\Database\Select                                                groupBy(array|string $expression)                                                                                          指定 GROUP BY 子句.
 * @method static \Leevel\Database\Select                                                having(...$cond)                                                                                                           添加一个 HAVING 条件.
 * @method static \Leevel\Database\Select                                                orHaving(...$cond)                                                                                                         orHaving 查询条件.
 * @method static \Leevel\Database\Select                                                havingRaw(string $raw)                                                                                                     having 原生查询.
 * @method static \Leevel\Database\Select                                                orHavingRaw(string $raw)                                                                                                   having 原生 OR 查询.
 * @method static \Leevel\Database\Select                                                havingBetween(...$cond)                                                                                                    havingBetween 查询条件.
 * @method static \Leevel\Database\Select                                                havingNotBetween(...$cond)                                                                                                 havingNotBetween 查询条件.
 * @method static \Leevel\Database\Select                                                havingNull(...$cond)                                                                                                       havingNull 查询条件.
 * @method static \Leevel\Database\Select                                                havingNotNull(...$cond)                                                                                                    havingNotNull 查询条件.
 * @method static \Leevel\Database\Select                                                havingIn(...$cond)                                                                                                         havingIn 查询条件.
 * @method static \Leevel\Database\Select                                                havingNotIn(...$cond)                                                                                                      havingNotIn 查询条件.
 * @method static \Leevel\Database\Select                                                havingLike(...$cond)                                                                                                       havingLike 查询条件.
 * @method static \Leevel\Database\Select                                                havingNotLike(...$cond)                                                                                                    havingNotLike 查询条件.
 * @method static \Leevel\Database\Select                                                havingDate(...$cond)                                                                                                       havingDate 查询条件.
 * @method static \Leevel\Database\Select                                                havingDay(...$cond)                                                                                                        havingDay 查询条件.
 * @method static \Leevel\Database\Select                                                havingMonth(...$cond)                                                                                                      havingMonth 查询条件.
 * @method static \Leevel\Database\Select                                                havingYear(...$cond)                                                                                                       havingYear 查询条件.
 * @method static \Leevel\Database\Select                                                orderBy(array|string $expression, string $orderDefault = 'ASC')                                                            添加排序.
 * @method static \Leevel\Database\Select                                                latest(string $field = 'create_at')                                                                                        最近排序数据.
 * @method static \Leevel\Database\Select                                                oldest(string $field = 'create_at')                                                                                        最早排序数据.
 * @method static \Leevel\Database\Select                                                distinct(bool $flag = true)                                                                                                创建一个 SELECT DISTINCT 查询.
 * @method static \Leevel\Database\Select                                                count(string $field = '*', string $alias = 'row_count')                                                                    总记录数.
 * @method static \Leevel\Database\Select                                                avg(string $field, string $alias = 'avg_value')                                                                            平均数.
 * @method static \Leevel\Database\Select                                                max(string $field, string $alias = 'max_value')                                                                            最大值.
 * @method static \Leevel\Database\Select                                                min(string $field, string $alias = 'min_value')                                                                            最小值.
 * @method static \Leevel\Database\Select                                                sum(string $field, string $alias = 'sum_value')                                                                            合计
 * @method static \Leevel\Database\Select                                                one()                                                                                                                      指示仅查询第一个符合条件的记录.
 * @method static \Leevel\Database\Select                                                all()                                                                                                                      指示查询所有符合条件的记录.
 * @method static \Leevel\Database\Select                                                top(int $count = 30)                                                                                                       查询几条记录.
 * @method static \Leevel\Database\Select                                                limit(int $offset = 0, int $count = 0)                                                                                     limit 限制条数.
 * @method static \Leevel\Database\Select                                                forUpdate(bool $flag = true)                                                                                               排它锁 FOR UPDATE 查询.
 * @method static \Leevel\Database\Select                                                lockShare(bool $flag = true)                                                                                               共享锁 LOCK SHARE 查询.
 * @method static array                                                                  getBindParams()                                                                                                            返回参数绑定.                                                                                                         返回参数绑定.
 * @method static void                                                                   resetBindParams(array $bindParams = [])                                                                                    重置参数绑定.
 * @method static void                                                                   setBindParamsPrefix(string $bindParamsPrefix)                                                                              设置参数绑定前缀.
 * @method static \Leevel\Database\Select                                                if(mixed $value = false)                                                                                                   条件语句 if.
 * @method static \Leevel\Database\Select                                                elif(mixed $value = false)                                                                                                 条件语句 elif.
 * @method static \Leevel\Database\Select                                                else()                                                                                                                     条件语句 else.
 * @method static \Leevel\Database\Select                                                fi()                                                                                                                       条件语句 fi.
 * @method static \Leevel\Database\Select                                                setFlowControl(bool $inFlowControl, bool $isFlowControlTrue)                                                               设置当前条件表达式状态.
 * @method static bool                                                                   checkFlowControl()                                                                                                         验证一下条件表达式是否通过.
 */
interface IDatabase extends IConnection
{
    /**
     * 断线重连尝试次数.
     */
    public const RECONNECT_MAX = 3;

    /**
     * 主服务 PDO 标识.
     */
    public const MASTER = 0;

    /**
     * SQL 日志事件.
     */
    public const SQL_EVENT = 'database.sql';

    /**
     * 设置缓存.
     */
    public function setCache(?ICache $cache): void;

    /**
     * 获取缓存.
     */
    public function getCache(): ?ICache;

    /**
     * 返回查询对象.
     */
    public function databaseSelect(): Select;

    /**
     * 返回 PDO 查询连接.
     *
     * - $master: bool,false (读服务器),true (写服务器)
     * - $master: int,其它去对应服务器连接 ID，\Leevel\Database\IDatabase::MASTER 表示主服务器
     */
    public function pdo(bool|int $master = false): ?\PDO;

    /**
     * 查询数据记录.
     */
    public function query(string $sql, array $bindParams = [], bool|int $master = false, ?string $cacheName = null, ?int $cacheExpire = null, ?ICache $cache = null): mixed; /** @codeCoverageIgnore */

    /**
     * 查询存储过程数据记录.
     */
    public function procedure(string $sql, array $bindParams = [], bool|int $master = false, ?string $cacheName = null, ?int $cacheExpire = null, ?ICache $cache = null): array; /** @codeCoverageIgnore */

    /**
     * 执行 SQL 语句.
     */
    public function execute(string $sql, array $bindParams = []): int|string; /** @codeCoverageIgnore */

    /**
     * 游标查询.
     */
    public function cursor(string $sql, array $bindParams = [], bool|int $master = false): \Generator; /** @codeCoverageIgnore */

    /**
     * SQL 预处理.
     *
     * - 记录 SQL 日志
     * - 支持重连
     */
    public function prepare(string $sql, array $bindParams = [], bool|int $master = false): \PDOStatement; /** @codeCoverageIgnore */

    /**
     * 执行数据库事务.
     */
    public function transaction(\Closure $action): mixed;

    /**
     * 启动事务.
     */
    public function beginTransaction(): void;

    /**
     * 检查是否处于事务中.
     */
    public function inTransaction(): bool;

    /**
     * 用于非自动提交状态下面的查询提交.
     */
    public function commit(): void;

    /**
     * 事务回滚.
     */
    public function rollBack(): void;

    /**
     * 设置是否启用部分事务.
     */
    public function setSavepoints(bool $savepoints): void;

    /**
     * 获取是否启用部分事务.
     */
    public function hasSavepoints(): bool;

    /**
     * 获取最后插入 ID 或者列.
     */
    public function lastInsertId(?string $name = null): string;

    /**
     * 获取最近一次查询的 SQL 语句.
     */
    public function getLastSql(): string;

    /**
     * 设置最近一次真实查询的 SQL 语句.
     */
    public function setRealLastSql(array $realLastSql): void;

    /**
     * 获取最近一次真实查询的 SQL 语句.
     */
    public function getRealLastSql(): array;

    /**
     * 返回影响记录.
     */
    public function numRows(): int;

    /**
     * 关闭数据库.
     */
    public function close(): void;

    /**
     * 释放 PDO 预处理查询.
     */
    public function freePDOStatement(): void;

    /**
     * 关闭数据库连接.
     */
    public function closeConnects(): void;

    /**
     * 从 PDO 预处理语句中获取原始 SQL 查询字符串.
     *
     * - This method borrows heavily from the pdo-debug package and is part of the pdo-debug package.
     *
     * @see https://github.com/panique/pdo-debug/blob/master/pdo-debug.php
     * @see https://stackoverflow.com/questions/210564/getting-raw-sql-query-string-from-pdo-prepared-statements
     * @see http://php.net/manual/en/pdo.constants.php
     */
    public static function getRawSql(string $sql, array $bindParams): string;

    /**
     * 释放当前连接.
     *
     * - 用于归还当前的数据库连接到连接池
     *
     * @todo 加入到各种ide-helper中
     */
    public function releaseConnect(): void;

    /**
     * DSN 解析.
     */
    public function parseDsn(array $config): string;

    /**
     * 取得数据库表名列表.
     */
    public function getTableNames(string $dbName, bool|int $master = false): array;

    /**
     * 取得数据库表字段信息.
     */
    public function getTableColumns(string $tableName, bool|int $master = false): array;

    /**
     * 取得数据库表唯一索引信息.
     */
    public function getUniqueIndex(string $tableName, bool|int $master = false): array;

    /**
     * SQL 字段格式化.
     */
    public function identifierColumn(string $name): string;

    /**
     * 分析查询条数.
     */
    public function limitCount(?int $limitCount = null, ?int $limitOffset = null): string;
}
