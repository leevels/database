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
 * (c) 2010-2020 http://queryphp.com All rights reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Leevel\Database\Ddd;

use ArrayAccess;
use BadMethodCallException;
use Closure;
use InvalidArgumentException;
use JsonSerializable;
use Leevel\Collection\Collection;
use Leevel\Database\Ddd\Relation\BelongsTo;
use Leevel\Database\Ddd\Relation\HasMany;
use Leevel\Database\Ddd\Relation\HasOne;
use Leevel\Database\Ddd\Relation\ManyMany;
use Leevel\Database\Ddd\Relation\Relation;
use Leevel\Database\ReplaceException;
use Leevel\Database\Select as DatabaseSelect;
use Leevel\Event\IDispatch;
use Leevel\I18n\Helper\gettext;
use function Leevel\I18n\Helper\gettext as __;
use Leevel\Support\IArray;
use Leevel\Support\IJson;
use function Leevel\Support\Str\camelize;
use Leevel\Support\Str\camelize;
use function Leevel\Support\Str\un_camelize;
use Leevel\Support\Str\un_camelize;
use RuntimeException;
use Throwable;

/**
 * 模型实体 Object Relational Mapping.
 *
 * - 为最大化避免 getter setter 属性与系统冲突，系统自身的属性均加前缀 leevel，设置以 with 开头.
 * - ORM 主要基于妖怪大神的 QeePHP V2 设计灵感，查询器基于这个版本构建.
 * - 例外参照了 Laravel 关联模型实现设计.
 * - Doctrine 和 Java Hibernate 中关于 getter setter 的设计
 *
 * @author Xiangmin Liu <635750556@qq.com>
 *
 * @since 2017.04.27
 * @since 2018.10 进行一次大规模重构
 * @since 1.0.0-beta.1 2019.04.24 getFoo 修改为 getterFoo，setBar 修改为 setterBar
 * @since 1.0.0-beta.5 2019.08.04 删除 __call 中的一些查询用法，getterFoo 修改为 getFoo，setterBar 修改为 setBar
 * @see https://github.com/dualface/qeephp2_x
 * @see https://github.com/laravel/framework
 * @see https://github.com/doctrine/doctrine2
 * @see http://hibernate.org/
 *
 * @version 1.0
 */
abstract class Entity implements IEntity, IArray, IJson, JsonSerializable, ArrayAccess
{
    /**
     * 已修改的模型实体属性.
     *
     * @var array
     */
    protected array $leevelChangedProp = [];

    /**
     * 黑白名单.
     *
     * @var array
     */
    protected array $leevelBlackWhites = [
        'construct_prop' => ['white' => [], 'black' => []],
        'create_prop'    => ['white' => [], 'black' => []],
        'update_prop'    => ['white' => [], 'black' => []],
        'show_prop'      => ['white' => [], 'black' => []],
    ];

    /**
     * 指示对象是否对应数据库中的一条记录.
     *
     * @var bool
     */
    protected bool $leevelNewed = true;

    /**
     * Replace 模式.
     *
     * - 先插入出现主键重复.
     * - false 表示非 replace 模式，其它值表示 replace 模式附带的 fill 数据.
     *
     * @var mixed
     */
    protected $leevelReplace = false;

    /**
     * 多对多关联中间实体.
     *
     * @var \Leevel\Database\Ddd\IEntity
     */
    protected $leevelRelationMiddle;

    /**
     * 持久化基础层.
     *
     * @var \Closure
     */
    protected $leevelFlush;

    /**
     * 即将持久化数据.
     *
     * @var array
     */
    protected $leevelFlushData;

    /**
     * 模型实体事件处理器.
     *
     * @var \Leevel\Event\IDispatch
     */
    protected static ?IDispatch $leevelDispatch = null;

    /**
     * 缓存驼峰法命名属性.
     *
     * @var array
     */
    protected static array $leevelCamelize = [];

    /**
     * 缓存下划线命名属性.
     *
     * @var array
     */
    protected static array $leevelUnCamelize = [];

    /**
     * 缓存 ENUM 格式化数据.
     *
     * @var array
     */
    protected static array $leevelEnums = [];

    /**
     * 是否为软删除数据.
     *
     * @var bool
     */
    protected $leevelSoftDelete = false;

    /**
     * 是否为软删除恢复数据.
     *
     * @var bool
     */
    protected $leevelSoftRestore = false;

    /**
     * 构造函数.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data = [], bool $fromStorage = false)
    {
        $className = static::class;
        foreach (['TABLE', 'ID', 'AUTO', 'STRUCT'] as $item) {
            if (!defined($className.'::'.$item)) {
                $e = sprintf('The entity const %s was not defined.', $item);

                throw new InvalidArgumentException($e);
            }
        }

        foreach (static::STRUCT as $field => $v) {
            foreach (['construct_prop', 'show_prop', 'create_prop', 'update_prop'] as $type) {
                foreach (['black', 'white'] as $bw) {
                    if (isset($v[$type.'_'.$bw]) && true === $v[$type.'_'.$bw]) {
                        $this->leevelBlackWhites[$type][$bw][] = $field;
                    }
                }
            }
        }

        if ($fromStorage) {
            $this->leevelNewed = false;
        }

        if ($data) {
            foreach ($this->normalizeWhiteAndBlack($data, 'construct_prop') as $prop => $value) {
                if (isset($data[$prop])) {
                    $this->withProp($prop, $data[$prop], !$fromStorage, true);
                }
            }
        }
    }

    /**
     * 获取数据数据.
     *
     * @return mixed
     */
    public function __get(string $prop)
    {
        return $this->prop($prop);
    }

    /**
     * 更新属性数据.
     *
     * @param mixed $value
     */
    public function __set(string $prop, $value): void
    {
        $this->withProp($prop, $value);
    }

    /**
     * 是否存在属性数据.
     */
    public function __isset(string $prop): bool
    {
        return $this->hasProp($prop);
    }

    /**
     * 删除属性数据.
     */
    public function __unset(string $prop): void
    {
        $this->withProp($prop, null);
    }

    /**
     * call.
     *
     * @throws \BadMethodCallException
     *
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        // getter
        if (0 === strpos($method, 'get')) {
            return $this->getter(lcfirst(substr($method, 3)));
        }

        // setter
        if (0 === strpos($method, 'set')) {
            $this->setter(lcfirst(substr($method, 3)), $args[0] ?? null);

            return $this;
        }

        // relation tips
        try {
            if ($this->isRelation($unCamelize = $this->normalize($method))) {
                $e = sprintf(
                    'Method `%s` is not exits,maybe you can try `%s::make()->loadRelation(\'%s\')`.',
                    $method, static::class, $unCamelize
                );

                throw new BadMethodCallException($e);
            }
        } catch (InvalidArgumentException $th) {
        }

        // other method tips
        $e = sprintf(
            'Method `%s` is not exits,maybe you can try `%s::select|make()->%s(...)`.',
            $method, static::class, $method
        );

        throw new BadMethodCallException($e);
    }

    /**
     * call static.
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic(string $method, array $args)
    {
        $e = sprintf(
            'Method `%s` is not exits,maybe you can try `%s::select|make()->%s(...)`.',
            $method, static::class, $method
        );

        throw new BadMethodCallException($e);
    }

    /**
     * 将模型实体转化为 JSON.
     */
    public function __toString(): string
    {
        return $this->toJson(...func_get_args());
    }

    /**
     * 创建新的实例.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public static function make(array $data = [], bool $fromStorage = false): IEntity
    {
        return new static($data, $fromStorage);
    }

    /**
     * 数据库查询集合对象.
     *
     * - 查询静态方法入口，更好的 IDE 用户体验.
     * - 屏蔽 __callStatic 防止 IDE 无法识别.
     *
     * @return \Leevel\Database\Ddd\Select
     */
    public static function select(int $softDeletedType = self::WITHOUT_SOFT_DELETED): Select
    {
        return new Select(new static(), $softDeletedType);
    }

    /**
     * 数据库查询集合对象.
     *
     * - 查询静态方法入口，更好的 IDE 用户体验.
     * - 屏蔽 __callStatic 防止 IDE 无法识别.
     * - select 别名，致敬经典 QeePHP.
     *
     * @return \Leevel\Database\Ddd\Select
     */
    public static function find(int $softDeletedType = self::WITHOUT_SOFT_DELETED): Select
    {
        return static::select($softDeletedType);
    }

    /**
     * 包含软删除数据的数据库查询集合对象.
     *
     * - 查询静态方法入口，更好的 IDE 用户体验.
     * - 屏蔽 __callStatic 防止 IDE 无法识别.
     * - 获取包含软删除的数据.
     *
     * @return \Leevel\Database\Ddd\Select
     */
    public static function withSoftDeleted(): Select
    {
        return static::select(static::WITH_SOFT_DELETED);
    }

    /**
     * 仅仅包含软删除数据的数据库查询集合对象.
     *
     * - 查询静态方法入口，更好的 IDE 用户体验.
     * - 屏蔽 __callStatic 防止 IDE 无法识别.
     * - 获取只包含软删除的数据.
     *
     * @return \Leevel\Database\Ddd\Select
     */
    public static function onlySoftDeleted(): Select
    {
        return static::select(static::ONLY_SOFT_DELETED);
    }

    /**
     * 数据库查询集合对象.
     */
    public static function selectCollection(int $softDeletedType = self::WITHOUT_SOFT_DELETED): DatabaseSelect
    {
        $select = static::meta()
            ->select()
            ->asClass(static::class, [true])
            ->asCollection();

        static::prepareSoftDeleted($select, $softDeletedType);

        return $select;
    }

    /**
     * 返回模型实体类的 meta 对象.
     *
     * @return \Leevel\Database\Ddd\IMeta
     */
    public static function meta(): IMeta
    {
        return Meta::instance(static::TABLE)
            ->setDatabaseConnect(static::connect());
    }

    /**
     * 数据库连接沙盒.
     *
     * @param mixed $connect
     *
     * @return mixed
     */
    public static function connectSandbox($connect, Closure $call)
    {
        $old = static::connect();
        static::withConnect($connect);

        try {
            $result = $call();
            static::withConnect($old);
        } catch (Throwable $th) {
            static::withConnect($old);

            throw $th;
        }

        return $result;
    }

    /**
     * 批量设置属性数据.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function withProps(array $data): IEntity
    {
        foreach ($data as $prop => $value) {
            $this->withProp($prop, $value);
        }

        return $this;
    }

    /**
     * 设置属性数据.
     *
     * @param mixed $value
     *
     * @throws \InvalidArgumentException
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function withProp(string $prop, $value, bool $force = true, bool $ignoreReadonly = false): IEntity
    {
        $prop = $this->normalize($prop);
        $this->validate($prop);

        if ($this->isRelation($prop)) {
            $e = sprintf('Cannot set a relation prop `%s` on entity `%s`.', $prop, static::class);

            throw new InvalidArgumentException($e);
        }

        $this->propSetter($prop, $value);

        if (!$force) {
            return $this;
        }

        if (false === $ignoreReadonly &&
            isset(static::STRUCT[$prop][self::READONLY]) &&
            true === static::STRUCT[$prop][self::READONLY]) {
            $e = sprintf('Cannot set a read-only prop `%s` on entity `%s`.', $prop, static::class);

            throw new InvalidArgumentException($e);
        }

        if (in_array($prop, $this->leevelChangedProp, true)) {
            return $this;
        }

        $this->leevelChangedProp[] = $prop;

        return $this;
    }

    /**
     * 获取属性数据.
     *
     * @return mixed
     */
    public function prop(string $prop)
    {
        $prop = $this->normalize($prop);
        $this->validate($prop);

        if (!$this->isRelation($prop)) {
            return $this->propGetter($prop);
        }

        return $this->loadRelationProp($prop);
    }

    /**
     * 是否存在属性数据.
     */
    public function hasProp(string $prop): bool
    {
        return null !== $this->prop($prop);
    }

    /**
     * 自动判断快捷方式.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function save(array $data = [], ?array $fill = null): IEntity
    {
        $this->saveEntry('save', $data, $fill);

        return $this;
    }

    /**
     * 新增快捷方式.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function create(array $data = [], ?array $fill = null): IEntity
    {
        $this->saveEntry('create', $data, $fill);

        return $this;
    }

    /**
     * 更新快捷方式.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function update(array $data = [], ?array $fill = null): IEntity
    {
        $this->saveEntry('update', $data, $fill);

        return $this;
    }

    /**
     * replace 快捷方式.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function replace(array $data = [], ?array $fill = null): IEntity
    {
        $this->saveEntry('replace', $data, $fill);

        return $this;
    }

    /**
     * 根据主键 ID 删除模型实体.
     */
    public static function destroy(array $ids, bool $forceDelete = false): int
    {
        return static::selectAndDestroyEntitys($ids, 'delete', $forceDelete);
    }

    /**
     * 根据主键 ID 强制删除模型实体.
     */
    public static function forceDestroy(array $ids): int
    {
        return static::destroy($ids, true);
    }

    /**
     * 删除模型实体.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function delete(bool $forceDelete = false): IEntity
    {
        if (false === $forceDelete && defined(static::class.'::DELETE_AT')) {
            return $this->softDelete();
        }

        static::validatePrimaryKey();
        $this->leevelFlush = function ($condition) {
            $this->handleEvent(static::BEFORE_DELETE_EVENT, $condition);
            $num = static::meta()->delete($condition);
            $this->handleEvent(static::AFTER_DELETE_EVENT);

            return $num;
        };
        $this->leevelFlushData = [$this->idCondition()];

        return $this;
    }

    /**
     * 强制删除模型实体.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function forceDelete(): IEntity
    {
        return $this->delete(true);
    }

    /**
     * 根据主键 ID 删除模型实体.
     */
    public static function softDestroy(array $ids): int
    {
        return static::selectAndDestroyEntitys($ids, 'softDelete');
    }

    /**
     * 从模型实体中软删除数据.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function softDelete(): IEntity
    {
        $this->leevelSoftDelete = true;
        $this->clearChanged();
        $this->withProp(static::deleteAtColumn(), time());

        return $this->update();
    }

    /**
     * 恢复软删除的模型实体.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function softRestore(): IEntity
    {
        $this->leevelSoftRestore = true;
        $this->clearChanged();
        $this->withProp(static::deleteAtColumn(), 0);

        return $this->update();
    }

    /**
     * 检查模型实体是否已经被软删除.
     */
    public function softDeleted(): bool
    {
        return (int) $this->prop(static::deleteAtColumn()) > 0;
    }

    /**
     * 获取软删除字段.
     *
     * @throws \InvalidArgumentException
     */
    public static function deleteAtColumn(): string
    {
        if (!defined(static::class.'::DELETE_AT')) {
            $e = sprintf(
                'Entity `%s` soft delete field was not defined.',
                static::class
            );

            throw new InvalidArgumentException($e);
        }

        $deleteAt = static::DELETE_AT;
        if (!static::hasField($deleteAt)) {
            $e = sprintf(
                'Entity `%s` soft delete field `%s` was not found.',
                static::class, $deleteAt
            );

            throw new InvalidArgumentException($e);
        }

        return $deleteAt;
    }

    /**
     * 数据持久化数据.
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function flush()
    {
        if (!$this->leevelFlush) {
            $e = sprintf('Entity `%s` has no data need to be flush.', static::class);

            throw new RuntimeException($e);
        }

        try {
            $leevelFlush = $this->leevelFlush;
            $result = $leevelFlush(...$this->leevelFlushData);
        } catch (ReplaceException $e) {
            if (false === $this->leevelReplace) {
                throw $e;
            }

            $this->leevelFlush = null;
            $this->leevelFlushData = null;
            $this->updateReal($this->leevelReplace);
            $this->leevelReplace = false;

            return $this->flush();
        }

        $this->leevelFlush = null;
        $this->leevelFlushData = null;
        $this->handleEvent(static::AFTER_SAVE_EVENT);

        return $result;
    }

    /**
     * 获取数据持久化数据.
     */
    public function flushData(): ?array
    {
        return $this->leevelFlushData;
    }

    /**
     * 确定对象是否对应数据库中的一条记录.
     */
    public function newed(): bool
    {
        return $this->leevelNewed;
    }

    /**
     * 获取主键.
     *
     * - 唯一标识符.
     *
     * @return mixed
     */
    public function id()
    {
        $result = [];
        foreach ($keys = static::primaryKeys() as $value) {
            if (!$tmp = $this->prop($value)) {
                continue;
            }
            $result[$value] = $tmp;
        }

        if (!$result) {
            return;
        }

        // 复合主键，但是数据不完整则忽略
        if (count($keys) > 1 && count($keys) !== count($result)) {
            return;
        }

        if (1 === count($result)) {
            $result = reset($result);
        }

        return $result;
    }

    /**
     * 从数据库重新读取当前对象的属性.
     */
    public function refresh(): void
    {
        $data = static::meta()
            ->select()
            ->where($this->idCondition())
            ->findOne();

        foreach ($data as $k => $v) {
            $this->withProp($k, $v, false);
        }
    }

    /**
     * 返回关联数据.
     *
     * @return mixed
     */
    public function loadRelationProp(string $prop)
    {
        if ($result = $this->relationProp($prop)) {
            return $result;
        }

        return $this->loadDataFromRelation($prop);
    }

    /**
     * 是否为关联属性.
     */
    public function isRelation(string $prop): bool
    {
        $prop = $this->normalize($prop);
        $this->validate($prop);

        $struct = static::STRUCT[$prop];
        if (isset($struct[self::BELONGS_TO]) ||
           isset($struct[self::HAS_MANY]) ||
           isset($struct[self::HAS_ONE]) ||
           isset($struct[self::MANY_MANY])) {
            return true;
        }

        return false;
    }

    /**
     * 读取关联.
     *
     * @throws \BadMethodCallException
     */
    public function loadRelation(string $prop): Relation
    {
        $prop = $this->normalize($prop);
        $this->validate($prop);
        $defined = static::STRUCT[$prop];

        $relationScope = null;
        if (isset($defined[self::RELATION_SCOPE])) {
            $call = [$this, 'relationScope'.ucfirst($defined[self::RELATION_SCOPE])];
            // 如果关联作用域为 private 会触发 __call 魔术方法中的异常
            if (!method_exists($this, $call[1])) {
                $e = sprintf(
                    'Relation scope `%s` of entity `%s` is not exits.',
                    $call[1], static::class,
                );

                throw new BadMethodCallException($e);
            }
            $relationScope = Closure::fromCallable($call);
        }

        if (isset($defined[self::BELONGS_TO])) {
            $this->validateRelationDefined($defined, [self::SOURCE_KEY, self::TARGET_KEY]);

            $relation = $this->belongsTo(
               $defined[self::BELONGS_TO],
               $defined[self::TARGET_KEY],
               $defined[self::SOURCE_KEY],
               $relationScope,
           );
        } elseif (isset($defined[self::HAS_MANY])) {
            $this->validateRelationDefined($defined, [self::SOURCE_KEY, self::TARGET_KEY]);

            $relation = $this->hasMany(
               $defined[self::HAS_MANY],
               $defined[self::TARGET_KEY],
               $defined[self::SOURCE_KEY],
               $relationScope,
           );
        } elseif (isset($defined[self::HAS_ONE])) {
            $this->validateRelationDefined($defined, [self::SOURCE_KEY, self::TARGET_KEY]);

            $relation = $this->hasOne(
               $defined[self::HAS_ONE],
               $defined[self::TARGET_KEY],
               $defined[self::SOURCE_KEY],
               $relationScope,
           );
        } elseif (isset($defined[self::MANY_MANY])) {
            $this->validateRelationDefined($defined, [
                self::MIDDLE_ENTITY, self::SOURCE_KEY, self::TARGET_KEY,
                self::MIDDLE_TARGET_KEY, self::MIDDLE_SOURCE_KEY,
            ]);

            $relation = $this->manyMany(
               $defined[self::MANY_MANY],
               $defined[self::MIDDLE_ENTITY],
               $defined[self::TARGET_KEY],
               $defined[self::SOURCE_KEY],
               $defined[self::MIDDLE_TARGET_KEY],
               $defined[self::MIDDLE_SOURCE_KEY],
               $relationScope,
           );
        }

        return $relation;
    }

    /**
     * 取得关联数据.
     *
     * @return mixed
     */
    public function relationProp(string $prop)
    {
        $this->validate($prop);

        return $this->propGetter($prop);
    }

    /**
     * 设置关联数据.
     *
     * @param mixed $value
     */
    public function withRelationProp(string $prop, $value): void
    {
        $this->validate($prop);
        $this->propSetter($prop, $value);
    }

    /**
     * 预加载关联.
     *
     * @return \Leevel\Database\Ddd\Select
     */
    public static function eager(array $relation): Select
    {
        return static::select()->eager($relation);
    }

    /**
     * 设置多对多中间实体.
     *
     * @param \Leevel\Database\Ddd\IEntity $middle
     */
    public function withMiddle(IEntity $middle): void
    {
        $this->leevelRelationMiddle = $middle;
    }

    /**
     * 获取多对多中间实体.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function middle(): ?IEntity
    {
        return $this->leevelRelationMiddle;
    }

    /**
     * 一对一关联.
     */
    public function hasOne(string $relatedEntityClass, string $targetKey, string $sourceKey, ?Closure $scope = null): HasOne
    {
        $entity = new $relatedEntityClass();
        $this->validateRelationField($entity, $targetKey);
        $this->validateRelationField($this, $sourceKey);

        return new HasOne($entity, $this, $targetKey, $sourceKey, $scope);
    }

    /**
     * 定义从属关系.
     */
    public function belongsTo(string $relatedEntityClass, string $targetKey, string $sourceKey, ?Closure $scope = null): BelongsTo
    {
        $entity = new $relatedEntityClass();
        $this->validateRelationField($entity, $targetKey);
        $this->validateRelationField($this, $sourceKey);

        return new BelongsTo($entity, $this, $targetKey, $sourceKey, $scope);
    }

    /**
     * 一对多关联.
     */
    public function hasMany(string $relatedEntityClass, string $targetKey, string $sourceKey, ?Closure $scope = null): HasMany
    {
        $entity = new $relatedEntityClass();
        $this->validateRelationField($entity, $targetKey);
        $this->validateRelationField($this, $sourceKey);

        return new HasMany($entity, $this, $targetKey, $sourceKey, $scope);
    }

    /**
     * 多对多关联.
     */
    public function manyMany(string $relatedEntityClass, string $middleEntityClass, string $targetKey, string $sourceKey, string $middleTargetKey, string $middleSourceKey, ?Closure $scope = null): ManyMany
    {
        $entity = new $relatedEntityClass();
        $middleEntity = new $middleEntityClass();

        $this->validateRelationField($entity, $targetKey);
        $this->validateRelationField($middleEntity, $middleTargetKey);
        $this->validateRelationField($this, $sourceKey);
        $this->validateRelationField($middleEntity, $middleSourceKey);

        return new ManyMany(
            $entity, $this, $middleEntity, $targetKey,
            $sourceKey, $middleTargetKey, $middleSourceKey,
            $scope,
        );
    }

    /**
     * 返回模型实体事件处理器.
     *
     * @return \Leevel\Event\IDispatch
     */
    public static function eventDispatch(): ?IDispatch
    {
        return static::$leevelDispatch;
    }

    /**
     * 设置模型实体事件处理器.
     */
    public static function withEventDispatch(?IDispatch $dispatch = null): void
    {
        static::$leevelDispatch = $dispatch;
    }

    /**
     * 注册模型实体事件.
     *
     * @param \Closure|\Leevel\Event\Observer|string $listener
     *
     * @throws \InvalidArgumentException
     */
    public static function event(string $event, $listener): void
    {
        if (null === static::$leevelDispatch &&
            static::lazyloadPlaceholder() && null === static::$leevelDispatch) {
            $e = 'Event dispatch was not set.';

            throw new InvalidArgumentException($e);
        }

        static::validateSupportEvent($event);
        static::$leevelDispatch->register(
            "entity.{$event}:".static::class,
            $listener
        );
    }

    /**
     * 执行模型实体事件.
     *
     * @param array ...$args
     */
    public function handleEvent(string $event, ...$args): void
    {
        if (null === static::$leevelDispatch) {
            return;
        }

        static::validateSupportEvent($event);
        array_unshift($args, $this);
        array_unshift($args, "entity.{$event}:".get_class($this));

        static::$leevelDispatch->handle(...$args);
    }

    /**
     * 返回受支持的事件.
     */
    public static function supportEvent(): array
    {
        return [
            static::BEFORE_SAVE_EVENT,
            static::AFTER_SAVE_EVENT,
            static::BEFORE_CREATE_EVENT,
            static::AFTER_CREATE_EVENT,
            static::BEFORE_UPDATE_EVENT,
            static::AFTER_UPDATE_EVENT,
            static::BEFORE_DELETE_EVENT,
            static::AFTER_DELETE_EVENT,
            static::BEFORE_SOFT_DELETE_EVENT,
            static::AFTER_SOFT_DELETE_EVENT,
            static::BEFORE_SOFT_RESTORE_EVENT,
            static::AFTER_SOFT_RESTORE_EVENT,
        ];
    }

    /**
     * 返回已经改变.
     */
    public function changed(): array
    {
        return $this->leevelChangedProp;
    }

    /**
     * 检测是否已经改变.
     */
    public function hasChanged(string $prop): bool
    {
        return in_array($prop, $this->leevelChangedProp, true);
    }

    /**
     * 将指定的属性设置已改变.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function addChanged(array $props): IEntity
    {
        foreach ($props as $prop) {
            if (in_array($prop, $this->leevelChangedProp, true)) {
                continue;
            }

            $this->leevelChangedProp[] = $prop;
        }

        return $this;
    }

    /**
     * 删除改变属性.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function deleteChanged(array $props): IEntity
    {
        $this->leevelChangedProp = array_values(array_diff($this->leevelChangedProp, $props));

        return $this;
    }

    /**
     * 清空改变属性.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    public function clearChanged(): IEntity
    {
        $this->leevelChangedProp = [];

        return $this;
    }

    /**
     * 返回主键字段.
     *
     * @return null|array|string
     */
    public static function primaryKey()
    {
        $keys = static::primaryKeys();
        if (!$keys) {
            return;
        }

        return 1 === count($keys) ? reset($keys) : $keys;
    }

    /**
     * 验证主键是否存在并返回主键字段.
     *
     * @throws \InvalidArgumentException
     *
     * @return array|string
     */
    public static function validatePrimaryKey()
    {
        if (null === $key = static::primaryKey()) {
            $e = sprintf('Entity %s has no primary key.', static::class);

            throw new InvalidArgumentException($e);
        }

        return $key;
    }

    /**
     * 返回主键字段.
     */
    public static function primaryKeys(): array
    {
        return (array) static::ID;
    }

    /**
     * 返回自动增长字段.
     *
     * @return string
     */
    public static function autoIncrement(): ?string
    {
        return static::AUTO;
    }

    /**
     * 返回字段名字.
     */
    public static function fields(): array
    {
        return static::STRUCT;
    }

    /**
     * 是否存在字段.
     */
    public static function hasField(string $field): bool
    {
        return array_key_exists($field, static::fields());
    }

    /**
     * 返回供查询的主键字段
     * 复合主键或者没有主键直接抛出异常.
     *
     * @throws \InvalidArgumentException
     */
    public static function singlePrimaryKey(): string
    {
        $key = static::primaryKey();
        if (!is_string($key)) {
            $e = sprintf('Entity %s do not have primary key or composite id not supported.', static::class);

            throw new InvalidArgumentException($e);
        }

        return $key;
    }

    /**
     * 返回供查询的主键字段值
     * 复合主键或者没有主键直接抛出异常.
     *
     * @return mixed
     */
    public function singleId()
    {
        static::singlePrimaryKey();

        return $this->id();
    }

    /**
     * 返回设置表.
     */
    public static function table(): string
    {
        return static::TABLE;
    }

    /**
     * 获取 enum.
     * 不存在返回 false.
     *
     * @param null|mixed $enum
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed
     */
    public static function enum(string $prop, $enum = null)
    {
        $prop = static::normalize($prop);
        $enumDefined = static::class.'::'.strtoupper($prop).'_ENUM';

        if (!defined($enumDefined)) {
            return false;
        }

        if (!isset(static::$leevelEnums[static::class]) ||
            !isset(static::$leevelEnums[static::class][$prop])) {
            $enums = constant($enumDefined);
            $enums = array_values($enums);

            foreach ($enums as &$e) {
                if (!isset($e[1])) {
                    $e = sprintf('Invalid enum in the field `%s` of entity `%s`.', $prop, static::class);

                    throw new InvalidArgumentException($e);
                }

                $e[1] = __($e[1]);
            }

            static::$leevelEnums[static::class][$prop] = $enums;
        } else {
            $enums = static::$leevelEnums[static::class][$prop];
        }

        if (null === $enum) {
            return $enums;
        }

        $enums = array_column($enums, 1, 0);
        $enumSep = explode(',', (string) $enum);

        foreach ($enumSep as $v) {
            if (!isset($enums[$v]) && !isset($enums[(int) $v])) {
                $e = sprintf('Value not a enum in the field `%s` of entity `%s`.', $prop, static::class);

                throw new InvalidArgumentException($e);
            }
            $result[] = $enums[$v] ?? $enums[(int) $v];
        }

        return implode(self::ENUM_SEPARATE, $result);
    }

    /**
     * 对象转数组.
     */
    public function toArray(): array
    {
        return $this->toArraySource(...func_get_args());
    }

    /**
     * 对象转 JSON.
     */
    public function toJson(?int $option = null): string
    {
        if (null === $option) {
            $option = JSON_UNESCAPED_UNICODE;
        }

        $args = func_get_args();
        array_shift($args);

        return json_encode($this->toArray(...$args), $option);
    }

    /**
     * 实现 JsonSerializable::jsonSerialize.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray(...func_get_args());
    }

    /**
     * 创建一个模型实体集合.
     */
    public function collection(array $entity = []): Collection
    {
        return new Collection($entity);
    }

    /**
     * 获取查询键值.
     *
     * @throws \InvalidArgumentException
     */
    public function idCondition(): array
    {
        static::validatePrimaryKey();

        if (null === $ids = $this->id()) {
            $e = sprintf('Entity %s has no primary key data.', static::class);

            throw new InvalidArgumentException($e);
        }

        if (!is_array($ids)) {
            $ids = [static::singlePrimaryKey() => $ids];
        }

        return $ids;
    }

    /**
     * 实现 ArrayAccess::offsetExists.
     *
     * @param string $index
     */
    public function offsetExists($index): bool
    {
        return $this->hasProp($index);
    }

    /**
     * 实现 ArrayAccess::offsetSet.
     *
     * @param string $index
     * @param mixed  $newval
     */
    public function offsetSet($index, $newval): void
    {
        $this->withProp($index, $newval);
    }

    /**
     * 实现 ArrayAccess::offsetGet.
     *
     * @param string $index
     *
     * @return mixed
     */
    public function offsetGet($index)
    {
        return $this->prop($index);
    }

    /**
     * 实现 ArrayAccess::offsetUnset.
     *
     * @param string $index
     */
    public function offsetUnset($index): void
    {
        $this->withProp($index, null);
    }

    /**
     * setter.
     *
     * @param mixed $value
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    abstract public function setter(string $prop, $value): IEntity;

    /**
     * getter.
     *
     * @return mixed
     */
    abstract public function getter(string $prop);

    /**
     * set database connect.
     *
     * @param mixed $connect
     */
    abstract public static function withConnect($connect): void;

    /**
     * get database connect.
     *
     * @return mixed
     */
    abstract public static function connect();

    /**
     * 验证事件是否受支持.
     *
     * @throws \InvalidArgumentException
     */
    protected static function validateSupportEvent(string $event): void
    {
        if (!in_array($event, static::supportEvent(), true)) {
            $e = sprintf('Event `%s` do not support.', $event);

            throw new InvalidArgumentException($e);
        }
    }

    /**
     * 是否定义属性.
     *
     * @throws \InvalidArgumentException
     */
    protected function hasPropDefined(string $prop): bool
    {
        $prop = $this->normalize($prop);
        if (!$this->hasField($prop)) {
            return false;
        }

        $prop = $this->asProp($prop);
        if (!property_exists($this, $prop)) {
            $e = sprintf('Prop `%s` of entity `%s` was not defined.', $prop, get_class($this));

            throw new InvalidArgumentException($e);
        }

        return true;
    }

    /**
     * 查找并删除实体.
     */
    protected static function selectAndDestroyEntitys(array $ids, string $type, bool $forceDelete = false): int
    {
        $entitys = static::select()
            ->whereIn(static::singlePrimaryKey(), $ids)
            ->findAll();

        /** @var \Leevel\Database\Ddd\IEntity $entity */
        foreach ($entitys as $entity) {
            $entity->{$type}($forceDelete)->flush();
        }

        return count($entitys);
    }

    /**
     * 准备软删除查询条件.
     *
     * @throws \InvalidArgumentException
     */
    protected static function prepareSoftDeleted(DatabaseSelect $select, int $softDeletedType): void
    {
        if (!defined(static::class.'::DELETE_AT')) {
            return;
        }

        switch ($softDeletedType) {
            case self::WITH_SOFT_DELETED:
                break;
            case self::ONLY_SOFT_DELETED:
                $select->where(static::deleteAtColumn(), '>', 0);

                break;
            case self::WITHOUT_SOFT_DELETED:
                $select->where(static::deleteAtColumn(), 0);

                break;
            default:
                $e = sprintf('Invalid soft deleted type %d.', $softDeletedType);

                throw new InvalidArgumentException($e);
        }
    }

    /**
     * 保存统一入口.
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    protected function saveEntry(string $method, array $data, ?array $fill = null): IEntity
    {
        foreach ($data as $k => $v) {
            $this->withProp($k, $v);
        }

        $this->handleEvent(static::BEFORE_SAVE_EVENT);

        // 程序通过内置方法统一实现
        switch (strtolower($method)) {
            case 'create':
                $this->createReal($fill);

                break;
            case 'update':
                $this->updateReal($fill);

                break;
            case 'replace':
                $this->replaceReal($fill);

                break;
            case 'save':
            default:
                $ids = $this->id();
                if (is_array($ids)) {
                    $this->replaceReal($fill);
                } else {
                    if (empty($ids)) {
                        $this->createReal($fill);
                    } else {
                        $this->updateReal($fill);
                    }
                }

                break;
        }

        return $this;
    }

    /**
     * 添加数据.
     *
     * @throws \InvalidArgumentException
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    protected function createReal(?array $fill = null): IEntity
    {
        $this->parseAutoFill('create', $fill);
        $saveData = $this->normalizeWhiteAndBlackChangedData('create');

        $this->leevelFlush = function ($saveData) {
            $this->handleEvent(static::BEFORE_CREATE_EVENT, $saveData);

            $lastInsertId = static::meta()->insert($saveData);
            if ($auto = $this->autoIncrement()) {
                $this->withProp($auto, $lastInsertId, false, true);
            }
            $this->leevelNewed = false;
            $this->clearChanged();

            $this->handleEvent(static::AFTER_CREATE_EVENT, $saveData);

            return $lastInsertId;
        };
        $this->leevelFlushData = [$saveData];

        return $this;
    }

    /**
     * 更新数据.
     *
     * @throws \RuntimeException
     *
     * @return \Leevel\Database\Ddd\IEntity
     */
    protected function updateReal(?array $fill = null): IEntity
    {
        $this->parseAutoFill('update', $fill);
        $saveData = $this->normalizeWhiteAndBlackChangedData('update');
        $condition = $this->idCondition();
        foreach ($condition as $field => $value) {
            if (isset($saveData[$field])) {
                unset($saveData[$field]);
            }
        }
        if (!$saveData) {
            $e = sprintf('Entity `%s` has no data need to be update.', static::class);

            throw new RuntimeException($e);
        }

        $this->leevelFlush = function ($condition, $saveData) {
            $this->handleEvent(static::BEFORE_UPDATE_EVENT, $saveData, $condition);
            if (true === $this->leevelSoftDelete) {
                $this->handleEvent(static::BEFORE_SOFT_DELETE_EVENT, $saveData, $condition);
            }
            if (true === $this->leevelSoftRestore) {
                $this->handleEvent(static::BEFORE_SOFT_RESTORE_EVENT, $saveData, $condition);
            }

            $num = static::meta()->update($condition, $saveData);
            $this->clearChanged();

            $this->handleEvent(static::AFTER_UPDATE_EVENT);
            if (true === $this->leevelSoftDelete) {
                $this->handleEvent(static::AFTER_SOFT_DELETE_EVENT);
                $this->leevelSoftDelete = false;
            }
            if (true === $this->leevelSoftRestore) {
                $this->handleEvent(static::AFTER_SOFT_RESTORE_EVENT);
                $this->leevelSoftRestore = false;
            }

            return $num;
        };
        $this->leevelFlushData = [$condition, $saveData];

        return $this;
    }

    /**
     * 模拟 replace 数据.
     */
    protected function replaceReal(?array $fill = null): void
    {
        $this->leevelReplace = $fill;
        $this->createReal($fill);
    }

    /**
     * 整理黑白名单变更数据.
     *
     * @param array $type
     */
    protected function normalizeWhiteAndBlackChangedData(string $type): array
    {
        $propKey = $this->normalizeWhiteAndBlack(
            array_flip($this->leevelChangedProp), $type.'_prop'
        );
        $saveData = $this->normalizeChangedData($propKey);

        return $saveData;
    }

    /**
     * 整理变更数据.
     */
    protected function normalizeChangedData(array $propKey): array
    {
        $saveData = [];
        foreach ($this->leevelChangedProp as $prop) {
            if (!array_key_exists($prop, $propKey)) {
                continue;
            }
            $saveData[$prop] = $this->prop($prop);
        }

        return $saveData;
    }

    /**
     * 取得 getter 数据.
     *
     * @return mixed
     */
    protected function propGetter(string $prop)
    {
        $method = 'get'.ucfirst($prop = $this->asProp($prop));

        if (method_exists($this, $method)) {
            return $this->{$method}($prop);
        }

        return $this->getter($prop);
    }

    /**
     * 设置 setter 数据.
     *
     * @param mixed $value
     */
    protected function propSetter(string $prop, $value): void
    {
        $method = 'set'.ucfirst($prop = $this->asProp($prop));

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else {
            $this->setter($prop, $value);
        }
    }

    /**
     * 自动填充.
     */
    protected function parseAutoFill(string $type, ?array $fill = null): void
    {
        if (null === $fill) {
            return;
        }

        foreach (static::STRUCT as $prop => $value) {
            if ($fill && !in_array($prop, $fill, true)) {
                continue;
            }

            if (array_key_exists($type.'_fill', $value)) {
                $this->normalizeFill($prop, $value[$type.'_fill']);
            }
        }
    }

    /**
     * 格式化自动填充.
     *
     * @param mixed $value
     */
    protected function normalizeFill(string $prop, $value): void
    {
        if (null === $value) {
            $camelizeClass = 'fill'.ucfirst($this->asProp($prop));
            if (method_exists($this, $camelizeClass)) {
                $value = $this->{$camelizeClass}($this->prop($prop));
            }
        }

        $this->withProp($prop, $value);
    }

    /**
     * 从关联中读取数据.
     *
     * @return mixed
     */
    protected function loadDataFromRelation(string $prop)
    {
        $relation = $this->loadRelation($prop);
        $result = $relation->sourceQuery();
        $this->withRelationProp($prop, $result);

        return $result;
    }

    /**
     * 校验并转换真实属性.
     */
    protected function realProp(string $prop): string
    {
        $this->validate($prop);

        return $this->asProp($prop);
    }

    /**
     * 验证 getter setter 属性.
     *
     * @throws \InvalidArgumentException
     */
    protected function validate(string $prop): void
    {
        $prop = $this->normalize($prop);

        if (!$this->hasPropDefined($prop)) {
            $e = sprintf('Entity `%s` prop or field of struct `%s` was not defined.', get_class($this), $prop);

            throw new InvalidArgumentException($e);
        }
    }

    /**
     * 校验关联字段定义.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateRelationDefined(array $defined, array $field): void
    {
        foreach ($field as $v) {
            if (!isset($defined[$v])) {
                $e = sprintf('Relation `%s` field was not defined.', $v);

                throw new InvalidArgumentException($e);
            }
        }
    }

    /**
     * 验证关联字段.
     *
     * @param \Leevel\Database\Ddd\IEntity $entity
     *
     * @throws \InvalidArgumentException
     */
    protected function validateRelationField(IEntity $entity, string $field): void
    {
        if (!$entity->hasField($field)) {
            $e = sprintf(
                'The field `%s`.`%s` of entity `%s` was not defined.',
                $entity->table(), $field, get_class($entity)
            );

            throw new InvalidArgumentException($e);
        }
    }

    /**
     * 格式化黑白名单数据.
     */
    protected function normalizeWhiteAndBlack(array $key, string $type): array
    {
        return $this->whiteAndBlack(
            $key,
            $this->leevelBlackWhites[$type]['white'],
            $this->leevelBlackWhites[$type]['black']
        );
    }

    /**
     * 对象转数组.
     */
    protected function toArraySource(array $white = [], array $black = [], array $relationWhiteAndBlack = []): array
    {
        if ($white || $black) {
            $prop = $this->whiteAndBlack($this->fields(), $white, $black);
        } else {
            $prop = $this->normalizeWhiteAndBlack($this->fields(), 'show_prop');
        }

        $result = [];
        foreach ($prop as $k => $option) {
            $isRelationProp = false;
            if ($this->isRelation($k)) {
                $isRelationProp = true;
                $value = $this->relationProp($k);
            } else {
                $value = $this->prop($k);
            }

            if (null === $value) {
                if (!array_key_exists(self::SHOW_PROP_NULL, $option)) {
                    continue;
                }
                $value = $option[self::SHOW_PROP_NULL];
            } elseif ($isRelationProp) {
                $value = $this->normalizeRelationValue($value, $k, $relationWhiteAndBlack);
            }

            $result[$k] = $value;
            if (!$isRelationProp && null !== $value) {
                $result = static::prepareEnum($k, $result);
            }
        }

        return $result;
    }

    /**
     * 整理关联属性数据.
     */
    protected function normalizeRelationValue(IArray $value, string $prop, array $relationWhiteAndBlack): array
    {
        if (isset($relationWhiteAndBlack[$prop])) {
            list($white, $black, $whiteAndBlack) = array_pad($relationWhiteAndBlack[$prop], 3, []);
        } else {
            $white = $black = $whiteAndBlack = [];
        }

        $value = $value->toArray($white, $black, $whiteAndBlack);

        return $value;
    }

    /**
     * 准备 enum 数据.
     */
    protected static function prepareEnum(string $prop, array $data): array
    {
        if (false === $enum = static::enum($prop, $data[$prop])) {
            return $data;
        }

        $data[$prop.'_'.self::ENUM] = $enum;

        return $data;
    }

    /**
     * 黑白名单数据解析.
     */
    protected function whiteAndBlack(array $key, array $white, array $black): array
    {
        if ($white) {
            $key = array_intersect_key($key, array_flip($white));
        } elseif ($black) {
            $key = array_diff_key($key, array_flip($black));
        }

        return $key;
    }

    /**
     * 延迟载入占位符.
     */
    protected static function lazyloadPlaceholder(): bool
    {
        return Lazyload::placeholder();
    }

    /**
     * 统一处理前转换下划线命名风格.
     */
    protected static function normalize(string $prop): string
    {
        if (isset(static::$leevelUnCamelize[$prop])) {
            return static::$leevelUnCamelize[$prop];
        }

        return static::$leevelUnCamelize[$prop] = un_camelize($prop);
    }

    /**
     * 返回转驼峰命名.
     */
    protected function asProp(string $prop): string
    {
        if (isset(static::$leevelCamelize[$prop])) {
            return static::$leevelCamelize[$prop];
        }

        return static::$leevelCamelize[$prop] = camelize($prop);
    }
}

// import fn.
class_exists(un_camelize::class);
class_exists(camelize::class);
class_exists(gettext::class);
