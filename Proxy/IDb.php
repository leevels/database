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

namespace Leevel\Database\Proxy;

use Closure;
use Leevel\Database\Condition;
use Leevel\Database\IDatabase;
use Leevel\Database\Select;
use PDO;

/**
 * 代理 database 接口.
 *
 * @author Xiangmin Liu <635750556@qq.com>
 *
 * @since 2019.06.06
 *
 * @version 1.0
 *
 * @see \Leevel\Database\IDatabase 请保持接口设计的一致性
 */
interface IDb
{
    /**
     * 返回 Pdo 查询连接.
     *
     * @param bool|int $master
     *                         - bool false (读服务器) true (写服务器)
     *                         - int 其它去对应服务器连接ID 0 表示主服务器
     *
     * @return mixed
     */
    public static function pdo($master = false);

    /**
     * 查询数据记录.
     *
     * @param string     $sql           sql 语句
     * @param array      $bindParams    sql 参数绑定
     * @param bool|int   $master
     * @param int        $fetchType
     * @param null|mixed $fetchArgument
     * @param array      $ctorArgs
     *
     * @return mixed
     */
    public static function query(string $sql, array $bindParams = [], $master = false, int $fetchType = PDO::FETCH_OBJ, $fetchArgument = null, array $ctorArgs = []);

    /**
     * 执行 sql 语句.
     *
     * @param string $sql        sql 语句
     * @param array  $bindParams sql 参数绑定
     *
     * @return int|string
     */
    public static function execute(string $sql, array $bindParams = []);

    /**
     * 执行数据库事务
     *
     * @param \Closure $action 事务回调
     *
     * @return mixed
     */
    public static function transaction(Closure $action);

    /**
     * 启动事务.
     */
    public static function beginTransaction(): void;

    /**
     * 检查是否处于事务中.
     *
     * @return bool
     */
    public static function inTransaction(): bool;

    /**
     * 用于非自动提交状态下面的查询提交.
     */
    public static function commit(): void;

    /**
     * 事务回滚.
     */
    public static function rollBack(): void;

    /**
     * 获取最后插入 ID 或者列.
     *
     * @param null|string $name 自增序列名
     *
     * @return string
     */
    public static function lastInsertId(?string $name = null): string;

    /**
     * 获取最近一次查询的 sql 语句.
     *
     * @return array
     */
    public static function lastSql(): array;

    /**
     * 返回影响记录.
     *
     * @return int
     */
    public static function numRows(): int;

    /**
     * 关闭数据库.
     */
    public static function close(): void;

    /**
     * 释放 PDO 预处理查询.
     */
    public static function freePDOStatement(): void;

    /**
     * 关闭数据库连接.
     */
    public static function closeConnects(): void;

    /**
     * sql 表达式格式化.
     *
     * @param string $sql
     * @param string $tableName
     *
     * @return string
     */
    public static function normalizeExpression(string $sql, string $tableName): string;

    /**
     * 表或者字段格式化（支持别名）.
     *
     * @param string      $name
     * @param null|string $alias
     * @param null|string $as
     *
     * @return string
     */
    public static function normalizeTableOrColumn(string $name, ?string $alias = null, ?string $as = null): string;

    /**
     * 字段格式化.
     *
     * @param string $key
     * @param string $tableName
     *
     * @return string
     */
    public static function normalizeColumn(string $key, string $tableName): string;

    /**
     * 字段值格式化.
     *
     * @param mixed $value
     * @param bool  $quotationMark
     *
     * @return mixed
     */
    public static function normalizeColumnValue($value, bool $quotationMark = true);

    /**
     * 分析 sql 类型数据.
     *
     * @param string $sql
     *
     * @return string
     */
    public static function normalizeSqlType(string $sql): string;

    /**
     * 分析绑定参数类型数据.
     *
     * @param mixed $value
     *
     * @return int
     */
    public static function normalizeBindParamType($value): int;

    /**
     * dsn 解析.
     *
     * @param array $option
     *
     * @return string
     */
    public static function parseDsn(array $option): string;

    /**
     * 取得数据库表名列表.
     *
     * @param string   $dbName
     * @param bool|int $master
     *
     * @return array
     */
    public static function tableNames(string $dbName, $master = false): array;

    /**
     * 取得数据库表字段信息.
     *
     * @param string   $tableName
     * @param bool|int $master
     *
     * @return array
     */
    public static function tableColumns(string $tableName, $master = false): array;

    /**
     * sql 字段格式化.
     *
     * @param mixed $name
     *
     * @return string
     */
    public static function identifierColumn($name): string;

    /**
     * 分析 limit.
     *
     * @param null|int $limitCount
     * @param null|int $limitOffset
     *
     * @return string
     */
    public static function limitCount(?int $limitCount = null, ?int $limitOffset = null): string;

    /**
     * 查询对象
     *
     * @return \Leevel\Database\Condition
     */
    public static function databaseCondition(): Condition;

    /**
     * 返回数据库连接对象
     *
     * @return \Leevel\Database\IDatabase
     */
    public static function databaseConnect(): IDatabase;

    /**
     * 占位符返回本对象
     *
     * @return \Leevel\Database\Select
     */
    public static function selfDatabaseSelect(): Select;

    /**
     * 指定返回 SQL 不做任何操作.
     *
     * @param bool $flag 指示是否不做任何操作只返回 SQL
     *
     * @return \Leevel\Database\Select
     */
    public static function sql(bool $flag = true): Select;

    /**
     * 设置是否查询主服务器.
     *
     * @param bool $master
     *
     * @return \Leevel\Database\Select
     */
    public static function master(bool $master = false): Select;

    /**
     * 设置查询参数.
     *
     * @param int        $fetchStyle
     * @param null|mixed $fetchArgument
     * @param array      $ctorArgs
     *
     * @return \Leevel\Database\Select
     */
    public static function fetchArgs(int $fetchStyle, $fetchArgument = null, array $ctorArgs = []): Select;

    /**
     * 设置以类返会结果.
     *
     * @param string $className
     * @param array  $args
     *
     * @return \Leevel\Database\Select
     */
    public static function asClass(string $className, array $args = []): Select;

    /**
     * 设置默认形式返回.
     *
     * @return \Leevel\Database\Select
     */
    public static function asDefault(): Select;

    /**
     * 设置是否以集合返回.
     *
     * @param bool $acollection
     *
     * @return \Leevel\Database\Select
     */
    public static function asCollection(bool $acollection = true): Select;

    /**
     * 原生 sql 查询数据 select.
     *
     * @param null|callable|\Leevel\Database\Select|string $data
     * @param array                                        $bind
     * @param bool                                         $flag 指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function select($data = null, array $bind = [], bool $flag = false);

    /**
     * 插入数据 insert (支持原生 sql).
     *
     * @param array|string $data
     * @param array        $bind
     * @param bool         $replace
     * @param bool         $flag    指示是否不做任何操作只返回 SQL
     *
     * @return null|array|int
     */
    public static function insert($data, array $bind = [], bool $replace = false, bool $flag = false);

    /**
     * 批量插入数据 insertAll.
     *
     * @param array $data
     * @param array $bind
     * @param bool  $replace
     * @param bool  $flag    指示是否不做任何操作只返回 SQL
     *
     * @return null|array|int
     */
    public static function insertAll(array $data, array $bind = [], bool $replace = false, bool $flag = false);

    /**
     * 更新数据 update (支持原生 sql).
     *
     * @param array|string $data
     * @param array        $bind
     * @param bool         $flag 指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function update($data, array $bind = [], bool $flag = false);

    /**
     * 更新某个字段的值
     *
     * @param string $column
     * @param mixed  $value
     * @param array  $bind
     * @param bool   $flag   指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function updateColumn(string $column, $value, array $bind = [], bool $flag = false);

    /**
     * 字段递增.
     *
     * @param string $column
     * @param int    $step
     * @param array  $bind
     * @param bool   $flag   指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function updateIncrease(string $column, int $step = 1, array $bind = [], bool $flag = false);

    /**
     * 字段减少.
     *
     * @param string $column
     * @param int    $step
     * @param array  $bind
     * @param bool   $flag   指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function updateDecrease(string $column, int $step = 1, array $bind = [], bool $flag = false);

    /**
     * 删除数据 delete (支持原生 sql).
     *
     * @param null|string $data
     * @param array       $bind
     * @param bool        $flag 指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function delete(?string $data = null, array $bind = [], bool $flag = false);

    /**
     * 清空表重置自增 ID.
     *
     * @param bool $flag 指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function truncate(bool $flag = false);

    /**
     * 返回一条记录.
     *
     * @param bool $flag 指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function findOne(bool $flag = false);

    /**
     * 返回所有记录.
     *
     * @param bool $flag 指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function findAll(bool $flag = false);

    /**
     * 返回最后几条记录.
     *
     * @param null|int $num
     * @param bool     $flag 指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function find(?int $num = null, bool $flag = false);

    /**
     * 返回一个字段的值
     *
     * @param string $field
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function value(string $field, bool $flag = false);

    /**
     * 返回一个字段的值(别名).
     *
     * @param string $field
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function pull(string $field, bool $flag = false);

    /**
     * 返回一列数据.
     *
     * @param mixed       $fieldValue
     * @param null|string $fieldKey
     * @param bool        $flag       指示是否不做任何操作只返回 SQL
     *
     * @return array
     */
    public static function list($fieldValue, ?string $fieldKey = null, bool $flag = false): array;

    /**
     * 数据分块处理.
     *
     * @param int      $count
     * @param \Closure $chunk
     */
    public static function chunk(int $count, Closure $chunk): void;

    /**
     * 数据分块处理依次回调.
     *
     * @param int     $count
     * @param Closure $each
     */
    public static function each(int $count, Closure $each): void;

    /**
     * 总记录数.
     *
     * @param string $field
     * @param string $alias
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return array|int
     */
    public static function findCount(string $field = '*', string $alias = 'row_count', bool $flag = false);

    /**
     * 平均数.
     *
     * @param string $field
     * @param string $alias
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function findAvg(string $field, string $alias = 'avg_value', bool $flag = false);

    /**
     * 最大值
     *
     * @param string $field
     * @param string $alias
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function findMax(string $field, string $alias = 'max_value', bool $flag = false);

    /**
     * 最小值
     *
     * @param string $field
     * @param string $alias
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function findMin(string $field, string $alias = 'min_value', bool $flag = false);

    /**
     * 合计
     *
     * @param string $field
     * @param string $alias
     * @param bool   $flag  指示是否不做任何操作只返回 SQL
     *
     * @return mixed
     */
    public static function findSum(string $field, string $alias = 'sum_value', bool $flag = false);

    /**
     * 分页查询.
     *
     * @param int    $currentPage
     * @param int    $perPage
     * @param bool   $flag
     * @param bool   $withTotal
     * @param string $column
     *
     * @return array
     */
    public static function page(int $currentPage, int $perPage = 10, bool $flag = false, bool $withTotal = true, string $column = '*'): array;

    /**
     * 分页查询.
     * 可以渲染 HTML.
     *
     * @param int    $currentPage
     * @param int    $perPage
     * @param bool   $flag
     * @param string $column
     * @param array  $option
     *
     * @return array
     */
    public static function pageHtml(int $currentPage, int $perPage = 10, bool $flag = false, string $column = '*', array $option = []): array;

    /**
     * 创建一个无限数据的分页查询.
     *
     * @param int   $currentPage
     * @param int   $perPage
     * @param bool  $flag
     * @param array $option
     *
     * @return array
     */
    public static function pageMacro(int $currentPage, int $perPage = 10, bool $flag = false, array $option = []): array;

    /**
     * 创建一个只有上下页的分页查询.
     *
     * @param int   $currentPage
     * @param int   $perPage
     * @param bool  $flag
     * @param array $option
     *
     * @return array
     */
    public static function pagePrevNext(int $currentPage, int $perPage = 10, bool $flag = false, array $option = []): array;

    /**
     * 取得分页查询记录数量.
     *
     * @param string $cols
     *
     * @return int
     */
    public static function pageCount(string $cols = '*'): int;

    /**
     * 获得查询字符串.
     *
     * @param $withLogicGroup
     *
     * @return string
     */
    public static function makeSql(bool $withLogicGroup = false): string;

    /**
     * 根据分页设置条件.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return \Leevel\Database\Condition
     */
    public static function forPage(int $page, int $perPage = 15): Condition;

    /**
     * 时间控制语句开始.
     *
     * @param string $type
     *
     * @return \Leevel\Database\Condition
     */
    public static function time(string $type = 'date'): Condition;

    /**
     * 时间控制语句结束.
     *
     * @return \Leevel\Database\Condition
     */
    public static function endTime(): Condition;

    /**
     * 重置查询条件.
     *
     * @param null|string $option
     *
     * @return \Leevel\Database\Condition
     */
    public static function reset(?string $option = null): Condition;

    /**
     * prefix 查询.
     *
     * @param string $prefix
     *
     * @return \Leevel\Database\Condition
     */
    public static function prefix(string $prefix): Condition;

    /**
     * 添加一个要查询的表及其要查询的字段.
     *
     * @param mixed        $table
     * @param array|string $cols
     *
     * @return \Leevel\Database\Select
     */
    public static function table($table, $cols = '*'): Select;

    /**
     * 获取表别名.
     *
     * @return string
     */
    public static function getAlias(): string;

    /**
     * 添加字段.
     *
     * @param mixed       $cols
     * @param null|string $table
     *
     * @return \Leevel\Database\Select
     */
    public static function columns($cols = '*', ?string $table = null): Select;

    /**
     * 设置字段.
     *
     * @param mixed       $cols
     * @param null|string $table
     *
     * @return \Leevel\Database\Select
     */
    public static function setColumns($cols = '*', ?string $table = null): Select;

    /**
     * where 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Select
     */
    public static function where(...$cond): Select;

    /**
     * orWhere 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function orWhere(...$cond): Condition;

    /**
     * Where 原生查询.
     *
     * @param string $raw
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereRaw(string $raw): Condition;

    /**
     * Where 原生 OR 查询.
     *
     * @param string $raw
     *
     * @return \Leevel\Database\Condition
     */
    public static function orWhereRaw(string $raw): Condition;

    /**
     * exists 方法支持
     *
     * @param mixed $exists
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereExists($exists): Condition;

    /**
     * not exists 方法支持
     *
     * @param mixed $exists
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereNotExists($exists): Condition;

    /**
     * whereBetween 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereBetween(...$cond): Condition;

    /**
     * whereNotBetween 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereNotBetween(...$cond): Condition;

    /**
     * whereNull 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereNull(...$cond): Condition;

    /**
     * whereNotNull 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereNotNull(...$cond): Condition;

    /**
     * whereIn 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereIn(...$cond): Condition;

    /**
     * whereNotIn 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereNotIn(...$cond): Condition;

    /**
     * whereLike 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereLike(...$cond): Condition;

    /**
     * whereNotLike 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereNotLike(...$cond): Condition;

    /**
     * whereDate 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereDate(...$cond): Condition;

    /**
     * whereDay 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereDay(...$cond): Condition;

    /**
     * whereMonth 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereMonth(...$cond): Condition;

    /**
     * whereYear 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function whereYear(...$cond): Condition;

    /**
     * 参数绑定支持
     *
     * @param mixed      $names
     * @param null|mixed $value
     * @param int        $type
     *
     * @return \Leevel\Database\Condition
     */
    public static function bind($names, $value = null, int $type = PDO::PARAM_STR): Condition;

    /**
     * index 强制索引（或者忽略索引）.
     *
     * @param array|string $indexs
     * @param string       $type
     *
     * @return \Leevel\Database\Condition
     */
    public static function forceIndex($indexs, $type = 'FORCE'): Condition;

    /**
     * index 忽略索引.
     *
     * @param array|string $indexs
     *
     * @return \Leevel\Database\Condition
     */
    public static function ignoreIndex($indexs): Condition;

    /**
     * join 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function join($table, $cols, ...$cond): Condition;

    /**
     * innerJoin 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function innerJoin($table, $cols, ...$cond): Condition;

    /**
     * leftJoin 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function leftJoin($table, $cols, ...$cond): Condition;

    /**
     * rightJoin 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function rightJoin($table, $cols, ...$cond): Condition;

    /**
     * fullJoin 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function fullJoin($table, $cols, ...$cond): Condition;

    /**
     * crossJoin 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function crossJoin($table, $cols, ...$cond): Condition;

    /**
     * naturalJoin 查询.
     *
     * @param mixed        $table   同 table $table
     * @param array|string $cols    同 table $cols
     * @param array        ...$cond 同 where $cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function naturalJoin($table, $cols, ...$cond): Condition;

    /**
     * 添加一个 UNION 查询.
     *
     * @param array|callable|string $selects
     * @param string                $type
     *
     * @return \Leevel\Database\Condition
     */
    public static function union($selects, string $type = 'UNION'): Condition;

    /**
     * 添加一个 UNION ALL 查询.
     *
     * @param array|callable|string $selects
     *
     * @return \Leevel\Database\Condition
     */
    public static function unionAll($selects): Condition;

    /**
     * 指定 GROUP BY 子句.
     *
     * @param array|string $expression
     *
     * @return \Leevel\Database\Condition
     */
    public static function groupBy($expression): Condition;

    /**
     * 添加一个 HAVING 条件
     * < 参数规范参考 where()方法 >.
     *
     * @param array $data
     *
     * @return \Leevel\Database\Condition
     */
    public static function having(...$cond): Condition;

    /**
     * orHaving 查询条件.
     *
     * @param array $data
     *
     * @return \Leevel\Database\Condition
     */
    public static function orHaving(...$cond): Condition;

    /**
     * Having 原生查询.
     *
     * @param string $raw
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingRaw(string $raw): Condition;

    /**
     * Having 原生 OR 查询.
     *
     * @param string $raw
     *
     * @return \Leevel\Database\Condition
     */
    public static function orHavingRaw(string $raw): Condition;

    /**
     * havingBetween 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingBetween(...$cond): Condition;

    /**
     * havingNotBetween 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingNotBetween(...$cond): Condition;

    /**
     * havingNull 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingNull(...$cond): Condition;

    /**
     * havingNotNull 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingNotNull(...$cond): Condition;

    /**
     * havingIn 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingIn(...$cond): Condition;

    /**
     * havingNotIn 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingNotIn(...$cond): Condition;

    /**
     * havingLike 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingLike(...$cond): Condition;

    /**
     * havingNotLike 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingNotLike(...$cond): Condition;

    /**
     * havingDate 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingDate(...$cond): Condition;

    /**
     * havingDay 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingDay(...$cond): Condition;

    /**
     * havingMonth 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingMonth(...$cond): Condition;

    /**
     * havingYear 查询条件.
     *
     * @param array ...$cond
     *
     * @return \Leevel\Database\Condition
     */
    public static function havingYear(...$cond): Condition;

    /**
     * 添加排序.
     *
     * @param array|string $expression
     * @param string       $orderDefault
     *
     * @return \Leevel\Database\Condition
     */
    public static function orderBy($expression, string $orderDefault = 'ASC'): Condition;

    /**
     * 最近排序数据.
     *
     * @param string $field
     *
     * @return \Leevel\Database\Condition
     */
    public static function latest(string $field = 'create_at'): Condition;

    /**
     * 最早排序数据.
     *
     * @param string $field
     *
     * @return \Leevel\Database\Condition
     */
    public static function oldest(string $field = 'create_at'): Condition;

    /**
     * 创建一个 SELECT DISTINCT 查询.
     *
     * @param bool $flag 指示是否是一个 SELECT DISTINCT 查询（默认 true）
     *
     * @return \Leevel\Database\Condition
     */
    public static function distinct(bool $flag = true): Condition;

    /**
     * 总记录数.
     *
     * @param string $field
     * @param string $alias
     *
     * @return \Leevel\Database\Condition
     */
    public static function count(string $field = '*', string $alias = 'row_count'): Condition;

    /**
     * 平均数.
     *
     * @param string $field
     * @param string $alias
     *
     * @return \Leevel\Database\Condition
     */
    public static function avg(string $field, string $alias = 'avg_value'): Condition;

    /**
     * 最大值
     *
     * @param string $field
     * @param string $alias
     *
     * @return \Leevel\Database\Condition
     */
    public static function max(string $field, string $alias = 'max_value'): Condition;

    /**
     * 最小值
     *
     * @param string $field
     * @param string $alias
     *
     * @return \Leevel\Database\Condition
     */
    public static function min(string $field, string $alias = 'min_value'): Condition;

    /**
     * 合计
     *
     * @param string $field
     * @param string $alias
     *
     * @return \Leevel\Database\Condition
     */
    public static function sum(string $field, string $alias = 'sum_value'): Condition;

    /**
     * 指示仅查询第一个符合条件的记录.
     *
     * @return \Leevel\Database\Condition
     */
    public static function one(): Condition;

    /**
     * 指示查询所有符合条件的记录.
     *
     * @return \Leevel\Database\Condition
     */
    public static function all(): Condition;

    /**
     * 查询几条记录.
     *
     * @param int $count
     *
     * @return \Leevel\Database\Condition
     */
    public static function top(int $count = 30): Condition;

    /**
     * limit 限制条数.
     *
     * @param int $offset
     * @param int $count
     *
     * @return \Leevel\Database\Condition
     */
    public static function limit(int $offset = 0, int $count = 0): Condition;

    /**
     * 是否构造一个 FOR UPDATE 查询.
     *
     * @param bool $flag
     *
     * @return \Leevel\Database\Condition
     */
    public static function forUpdate(bool $flag = true): Condition;

    /**
     * 设置查询参数.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return \Leevel\Database\Condition
     */
    public static function setOption(string $name, $value): Condition;

    /**
     * 返回查询参数.
     *
     * @return array
     */
    public static function getOption(): array;

    /**
     * 返回参数绑定.
     *
     * @return array
     */
    public static function getBindParams(): array;
}