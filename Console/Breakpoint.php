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

namespace Leevel\Database\Console;

use Leevel\Database\Console\Virtual\Breakpoint as VirtualBreakpoint;
use Phinx\Console\Command\Breakpoint as PhinxBreakpoint;

// @codeCoverageIgnoreStart
if (class_exists(PhinxBreakpoint::class)) {
    class_alias(PhinxBreakpoint::class, __NAMESPACE__.'\\BaseBreakpoint');
} else {
    class_alias(VirtualBreakpoint::class, __NAMESPACE__.'\\BaseBreakpoint');
}
/** @codeCoverageIgnoreEnd */

/**
 * 数据库迁移设置断点.
 *
 * @codeCoverageIgnore
 */
class Breakpoint extends BaseBreakpoint
{
    /**
     * Configures the current command.
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('migrate:breakpoint');
    }
}
