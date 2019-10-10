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

namespace Leevel\Database\Console;

use Leevel\Database\Console\Virtual\Test as VirtualTest;
use Phinx\Console\Command\Test as PhinxTest;

// @codeCoverageIgnoreStart
if (class_exists(PhinxTest::class)) {
    class_alias(PhinxTest::class, __NAMESPACE__.'\\BaseTest');
} else {
    class_alias(VirtualTest::class, __NAMESPACE__.'\\BaseTest');
}
/** @codeCoverageIgnoreEnd */

/**
 * 数据库测试环境是否正常.
 *
 * @author Xiangmin Liu <635750556@qq.com>
 *
 * @since 2017.05.09
 *
 * @version 1.0
 * @codeCoverageIgnore
 */
class Test extends BaseTest
{
    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('migrate:test');
    }
}
