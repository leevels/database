<?php

declare(strict_types=1);

namespace Leevel\Database\Ddd\Relation;

use Leevel\Database\Condition;
use Leevel\Database\Ddd\Entity;
use Leevel\Database\Ddd\EntityCollection;
use Leevel\Database\Ddd\Select;

/**
 * 关联实体 ManyMany.
 */
class ManyMany extends Relation
{
    /**
     * 中间实体.
     */
    protected Entity $middleEntity;

    /**
     * 目标中间实体关联字段.
     */
    protected string $middleTargetKey;

    /**
     * 源中间实体关联字段.
     */
    protected string $middleSourceKey;

    /**
     * 中间实体只包含软删除的数据.
     */
    protected bool $middleOnlySoftDeleted = false;

    /**
     * 中间实体包含软删除的数据.
     */
    protected bool $middleWithSoftDeleted = false;

    /**
     * 中间实体查询字段.
     */
    protected array $middleField = [];

    /**
     * 构造函数.
     */
    public function __construct(Entity $targetEntity, Entity $sourceEntity, Entity $middleEntity, string $targetKey, string $sourceKey, string $middleTargetKey, string $middleSourceKey, ?\Closure $scope = null)
    {
        $this->middleEntity = $middleEntity;
        $this->middleTargetKey = $middleTargetKey;
        $this->middleSourceKey = $middleSourceKey;

        parent::__construct($targetEntity, $sourceEntity, $targetKey, $sourceKey, $scope);
    }

    /**
     * 中间实体包含软删除数据的实体查询对象.
     *
     * - 获取包含软删除的数据.
     */
    public function middleWithSoftDeleted(bool $middleWithSoftDeleted = true): self
    {
        $this->middleWithSoftDeleted = $middleWithSoftDeleted;

        return $this;
    }

    /**
     * 中间实体仅仅包含软删除数据的实体查询对象.
     *
     * - 获取只包含软删除的数据.
     */
    public function middleOnlySoftDeleted(bool $middleOnlySoftDeleted = true): self
    {
        $this->middleOnlySoftDeleted = $middleOnlySoftDeleted;

        return $this;
    }

    /**
     * 中间实体查询字段.
     */
    public function middleField(array $middleField = []): self
    {
        $this->middleField = $middleField;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function addRelationCondition(): void
    {
        $this->prepareRelationCondition(function ($sourceValue): void {
            $this->selectRelationData([$sourceValue]);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function preLoadCondition(array $entities): void
    {
        if (!$sourceValue = $this->getPreLoadSourceValue($entities)) {
            $this->emptySourceData = true;

            return;
        }

        $this->emptySourceData = false;
        $this->selectRelationData($sourceValue);
    }

    /**
     * {@inheritDoc}
     */
    public function matchPreLoad(array $entities, EntityCollection $result, string $relation): array
    {
        $maps = $this->buildMap($result);
        foreach ($entities as $entity) {
            $key = $entity->prop($this->sourceKey);
            $entity->withRelationProp(
                $relation,
                $this->targetEntity->collection($maps[$key] ?? []),
            );
        }

        return $entities;
    }

    /**
     * {@inheritDoc}
     */
    public function sourceQuery(): mixed
    {
        if ($this->emptySourceData) {
            return $this->targetEntity->collection();
        }

        $tempsData = Select::withoutPreLoadsResult(function () {
            return $this->select->findAll();
        });
        if (!$tempsData) {
            return $this->targetEntity->collection();
        }

        $result = [];
        $middleEntityClass = $this->middleEntity::class;
        $targetEntityClass = $this->targetEntity::class;
        // @phpstan-ignore-next-line
        foreach ($tempsData as $value) {
            $value = (array) $value;
            $middleEntity = new $middleEntityClass($this->normalizeMiddelEntityData($value), true, true);
            $targetEntity = new $targetEntityClass($value, true, true);
            $targetEntity->withMiddle($middleEntity);
            $result[] = $targetEntity;
        }

        return $this->targetEntity->collection($result);
    }

    /**
     * {@inheritDoc}
     */
    public function getPreLoad(): mixed
    {
        return $this->getSelect()->preLoadResult($this->sourceQuery());
    }

    /**
     * 取得中间实体.
     */
    public function getMiddleEntity(): Entity
    {
        return $this->middleEntity;
    }

    /**
     * 取得目标中间实体关联字段.
     */
    public function getMiddleTargetKey(): string
    {
        return $this->middleTargetKey;
    }

    /**
     * 取得源中间实体关联字段.
     */
    public function getMiddleSourceKey(): string
    {
        return $this->middleSourceKey;
    }

    /**
     * 整理中间实体数据.
     */
    protected function normalizeMiddelEntityData(array &$value): array
    {
        $middelData = [
            $this->middleSourceKey => $value['middle_'.$this->middleSourceKey],
            $this->middleTargetKey => $value['middle_'.$this->middleTargetKey],
        ];
        unset(
            $value['middle_'.$this->middleSourceKey],
            $value['middle_'.$this->middleTargetKey]
        );

        foreach ($this->middleField as $middleFieldAlias => $middleField) {
            $middleFieldAlias = \is_string($middleFieldAlias) ? $middleFieldAlias : $middleField;
            $middelData[$middleField] = $value[$middleFieldAlias];
            unset($value[$middleFieldAlias]);
        }

        return $middelData;
    }

    /**
     * 查询关联数据.
     */
    protected function selectRelationData(array $sourceValue): void
    {
        $this->emptySourceData = false;
        $middleCondition = [
            $this->middleTargetKey => Condition::raw('['.$this->targetEntity->table().'.'.$this->targetKey.']'),
        ];
        $this->prepareMiddleSoftDeleted($middleCondition);

        $middleField = array_merge($this->middleField, [
            'middle_'.$this->middleTargetKey => $this->middleTargetKey,
            'middle_'.$this->middleSourceKey => $this->middleSourceKey,
        ]);

        $this->select
            ->join(
                $this->middleEntity->table(),
                $middleField,
                $middleCondition,
            )
            ->whereIn(
                $this->middleEntity->table().'.'.$this->middleSourceKey,
                $sourceValue,
            )
            ->asSome()
            ->asCollection(false)
        ;
    }

    /**
     * 中间实体软删除处理.
     */
    protected function prepareMiddleSoftDeleted(array &$middleCondition): void
    {
        if (!\defined($this->middleEntity::class.'::DELETE_AT')) {
            return;
        }

        if ($this->middleWithSoftDeleted) {
            return;
        }

        if ($this->middleOnlySoftDeleted) {
            $value = ['>', 0];
        } else {
            $value = 0;
        }

        $field = $this->middleEntity->table().'.'.$this->middleEntity::deleteAtColumn();
        $middleCondition[$field] = $value;
    }

    /**
     * 取回源实体对应数据.
     */
    protected function getPreLoadSourceValue(array $entitys): array
    {
        $data = [];
        foreach ($entitys as $sourceEntity) {
            if ($value = $sourceEntity->prop($this->sourceKey)) {
                $data[] = $value;
            }
        }

        return $data;
    }

    /**
     * 实体映射数据.
     */
    protected function buildMap(EntityCollection $result): array
    {
        $maps = [];

        /** @var Entity $entity */
        foreach ($result as $entity) {
            // @phpstan-ignore-next-line
            $maps[$entity->middle()->prop($this->middleSourceKey)][] = $entity;
        }

        return $maps;
    }
}
