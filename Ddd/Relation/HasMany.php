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

namespace Leevel\Database\Ddd\Relation;

use Leevel\Collection\Collection;
use Leevel\Database\Ddd\Select;

/**
 * 关联模型实体 HasMany.
 *
 * @author Xiangmin Liu <635750556@qq.com>
 *
 * @since 2017.09.28
 *
 * @version 1.0
 */
class HasMany extends Relation
{
    /**
     * 关联基础查询条件.
     */
    public function addRelationCondition(): void
    {
        $this->prepareRelationCondition(function ($sourceValue): void {
            $this->select->where($this->targetKey, $sourceValue);
        });
    }

    /**
     * 设置预载入关联查询条件.
     *
     * @param \Leevel\Database\Ddd\IEntity[] $entitys
     */
    public function preLoadCondition(array $entitys): void
    {
        if (!$sourceValue = $this->getEntityKey($entitys, $this->sourceKey)) {
            $this->emptySourceData = true;

            return;
        }

        $this->emptySourceData = false;
        $this->select->whereIn($this->targetKey, $sourceValue);
    }

    /**
     * 匹配关联查询数据到模型实体 HasMany.
     *
     * @param \Leevel\Database\Ddd\IEntity[] $entitys
     * @param \Leevel\Collection\Collection  $result
     * @param string                         $relation
     *
     * @return array
     */
    public function matchPreLoad(array $entitys, Collection $result, string $relation): array
    {
        return $this->matchPreLoadOneOrMany(
            $entitys,
            $result,
            $relation,
            'many'
        );
    }

    /**
     * 查询关联对象
     *
     * @return mixed
     */
    public function sourceQuery()
    {
        if (true === $this->emptySourceData) {
            return new Collection();
        }

        return Select::withoutPreLoadsResult(function () {
            return $this->select->findAll();
        });
    }

    /**
     * 匹配预载入数据.
     *
     * @param \Leevel\Database\Ddd\IEntity[] $entitys
     * @param \Leevel\Collection\Collection  $result
     * @param string                         $relation
     * @param string                         $type
     *
     * @return array
     */
    protected function matchPreLoadOneOrMany(array $entitys, Collection $result, string $relation, string $type): array
    {
        $maps = $this->buildMap($result);
        foreach ($entitys as &$entity) {
            $key = $entity->prop($this->sourceKey);
            $entity->withRelationProp(
                $relation,
                $this->getRelationValue($maps[$key] ?? [], $type)
            );
        }

        return $entitys;
    }

    /**
     * 取得关联模型实体数据.
     *
     * @param \Leevel\Database\Ddd\IEntity[] $entitys
     * @param string                         $type
     *
     * @return mixed
     */
    protected function getRelationValue(array $entitys, string $type)
    {
        if (!$entitys) {
            return 'one' === $type ?
                $this->targetEntity->make() :
                $this->targetEntity->collection();
        }

        return 'one' === $type ?
            reset($entitys) :
            $this->targetEntity->collection($entitys);
    }

    /**
     * 模型实体映射数据.
     *
     * @param \Leevel\Collection\Collection $result
     *
     * @return array
     */
    protected function buildMap(Collection $result): array
    {
        $maps = [];
        foreach ($result as $value) {
            $maps[$value->prop($this->targetKey)][] = $value;
        }

        return $maps;
    }
}
