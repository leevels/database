<?php

declare(strict_types=1);

namespace Leevel\Database\Console;

use Leevel\Console\Make;
use Leevel\Database\Manager;
use Leevel\Kernel\IApp;
use Leevel\Support\Str\Camelize;
use Leevel\Support\Str\UnCamelize;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * 生成实体.
 */
class Entity extends Make
{
    /**
     * 命令名字.
     */
    protected string $name = 'make:entity';

    /**
     * 命令描述.
     */
    protected string $description = 'Create a new entity';

    /**
     * 命令帮助.
     */
    protected string $help = <<<'EOF'
        The <info>%command.name%</info> command to make entity:

          <info>php %command.full_name% name</info>

        You can also by using the <comment>Job::Job</comment> to assign app and table:

          <info>php %command.full_name% Job::Job</info>

        You can also by using the <comment>--table</comment> config:

          <info>php %command.full_name% name --table=test</info>

        You can also by using the <comment>--stub</comment> config:

          <info>php %command.full_name% name --stub=stub/entity</info>

        You can also by using the <comment>--force</comment> config:

          <info>php %command.full_name% name --force</info>

        You can also by using the <comment>--refresh</comment> config:

          <info>php %command.full_name% name --refresh</info>

        You can also by using the <comment>--connect</comment> config:

        <info>php %command.full_name% name --connect=db_product</info>
        EOF;

    /**
     * 数据库仓储.
     */
    protected Manager $database; /** @phpstan-ignore-line */

    /**
     * 应用.
     */
    protected IApp $app; /** @phpstan-ignore-line */

    /**
     * 刷新临时模板文件.
     */
    protected ?string $tempTemplatePath = null;

    /**
     * 刷新原实体结构数据.
     */
    protected array $oldStructData = [];

    /**
     * 应用的 composer 配置.
     */
    protected ?array $composerConfig = null;

    /**
     * 表名.
     */
    protected string $tableName = '';

    /**
     * 响应命令.
     */
    public function handle(Manager $database, IApp $app): int
    {
        $this->database = $database;
        $this->app = $app;

        $this->parseAppTable();

        // 设置模板路径
        $this->setTemplatePath($this->getStubPath());

        // 保存路径
        $this->setSaveFilePath($this->parseSaveFilePath());

        // 处理强制更新
        $this->handleForce();

        // 处理刷新
        $this->handleRefresh();

        // 设置自定义变量替换
        $this->setCustomReplaceKeyValue($this->getReplace());

        // 设置类型
        $this->setMakeType('entity');

        // 执行
        $this->create();

        // 清理
        $this->clear();

        return self::SUCCESS;
    }

    /**
     * 分析文件保存路径.
     */
    protected function parseSaveFilePath(): string
    {
        return $this->getNamespacePath().'Entity/'.
            ucfirst(Camelize::handle($this->tableName)).'.php';
    }

    /**
     * 执行清理.
     */
    protected function clear(): void
    {
        if ($this->tempTemplatePath && is_file($this->tempTemplatePath)) {
            unlink($this->tempTemplatePath);
        }
    }

    /**
     * 处理强制更新.
     */
    protected function handleForce(): void
    {
        if (true !== $this->getOption('force')) {
            return;
        }

        if (is_file($file = $this->getSaveFilePath())) {
            unlink($file);
        }
    }

    /**
     * 处理刷新.
     */
    protected function handleRefresh(): void
    {
        if ($this->getOption('force')) {
            return;
        }

        if (!$this->getOption('refresh')) {
            return;
        }

        if (!is_file($file = $this->getSaveFilePath())) {
            return;
        }

        $contentLines = explode(PHP_EOL, file_get_contents($file) ?: '');
        [$startStructIndex, $endStructIndex] = $this->computeStructStartAndEndPosition($contentLines);

        $this->parseOldStructData(
            $contentLines,
            $startStructIndex,
            $endStructIndex,
        );

        $this->setRefreshTemplatePath(
            $contentLines,
            $startStructIndex,
            $endStructIndex,
        );

        unlink($file);
    }

    /**
     * 分析旧的字段结构数据.
     */
    protected function parseOldStructData(array $contentLines, int $middleStructIndex, int $endStructIndex): void
    {
        $oldStructData = [];
        $contentLines = $this->normalizeOldStructData($contentLines, $middleStructIndex, $endStructIndex);
        $regex = '/#\[Struct\(\[[\s\S]+?\]\)\][\s\S]+?protected[\s]*[\S]+?[\s]*\$([\S]+?)[\s]*=[\s]*[\S]+?;/';
        if (preg_match_all($regex, $contentLines, $matches)) {
            foreach ($matches[1] as $i => $v) {
                $oldStructData[UnCamelize::handle($v)] = '    '.trim($matches[0][$i], PHP_EOL).PHP_EOL;
            }
        }

        $this->oldStructData = $oldStructData;
    }

    /**
     * 整理旧的字段结构数据.
     */
    protected function normalizeOldStructData(array $contentLines, int $middleStructIndex, int $endStructIndex): string
    {
        $structLines = \array_slice(
            $contentLines,
            $middleStructIndex,
            $endStructIndex - $middleStructIndex + 2,
        );

        return implode(PHP_EOL, $structLines);
    }

    /**
     * 设置刷新模板.
     *
     * @throws \RuntimeException
     * @throws \Exception
     */
    protected function setRefreshTemplatePath(array $contentLines, int $startStructIndex, int $endStructIndex): void
    {
        $contentLines = $this->replaceStuctContentWithTag(
            $contentLines,
            $startStructIndex,
            $endStructIndex,
        );

        $tempTemplatePath = tempnam(sys_get_temp_dir(), 'leevel_entity');
        if (false === $tempTemplatePath) {
            throw new \Exception('Create unique file name failed.'); // @codeCoverageIgnore
        }
        $this->tempTemplatePath = $tempTemplatePath;
        file_put_contents($tempTemplatePath, implode(PHP_EOL, $contentLines));
        $this->setTemplatePath($tempTemplatePath);
    }

    /**
     * 替换字段结构内容为标记.
     */
    protected function replaceStuctContentWithTag(array $contentLines, int $startStructIndex, int $endStructIndex): array
    {
        for ($i = $startStructIndex + 1; $i < $endStructIndex + 2; ++$i) {
            unset($contentLines[$i]);
        }

        $contentLines[$startStructIndex] = '{{struct}}';
        ksort($contentLines);

        return $contentLines;
    }

    /**
     * 计算原实体内容中字段所在行起始和结束位置.
     *
     * @throws \Exception
     */
    protected function computeStructStartAndEndPosition(array $contentLines): array
    {
        $startStructIndex = $endStructIndex = 0;
        foreach ($contentLines as $i => $v) {
            $v = trim($v);
            if (!$startStructIndex && str_starts_with($v, '#[Struct([')) {
                $startStructIndex = $i;
            } elseif ('])]' === $v) {
                $endStructIndex = $i;
            }
        }

        if (!$endStructIndex || $startStructIndex > $endStructIndex) {
            throw new \Exception('Can not find start and end position of struct.');
        }

        return [$startStructIndex, $endStructIndex];
    }

    /**
     * 获取实体替换信息.
     */
    protected function getReplace(): array
    {
        $columns = $this->getColumns();
        $uniqueIndex = $this->getUniqueIndex();

        return [
            'app_name' => ucfirst($this->appName),
            'file_name' => ucfirst(Camelize::handle($this->tableName)),
            'table_name' => $tableName = $this->getTableName(),
            'file_title' => $columns['table_comment'] ?: $tableName,
            'primary_key' => $this->getPrimaryKey($columns),
            'auto_increment' => $this->getAutoIncrement($columns),
            'unique_index' => $this->parseUniqueIndex($uniqueIndex),
            'struct' => $this->getStruct($columns),
            'const_extend' => $this->getConstExtend($columns),
        ];
    }

    /**
     * 获取主键信息.
     */
    protected function getPrimaryKey(array $columns): string
    {
        if (!$columns['primary_key']) {
            return 'null';
        }

        if (\count($columns['primary_key']) > 1) {
            return '['.implode(', ', array_map(function ($item) {
                return "'{$item}'";
            }, $columns['primary_key'])).']';
        }

        return "'{$columns['primary_key'][0]}'";
    }

    /**
     * 获取自增信息.
     */
    protected function getAutoIncrement(array $columns): string
    {
        return $columns['auto_increment'] ?
            "'{$columns['auto_increment']}'" : 'null';
    }

    /**
     * 获取结构信息.
     */
    protected function getStruct(array $columns): string
    {
        $showPropBlackColumn = $this->composerConfig()['show_prop_black'];
        $struct = [];
        foreach ($columns['list'] as $val) {
            if (str_contains($val['comment'], ' ')) {
                [$val['comment'], $val['comment_extend']] = explode(' ', $val['comment'], 2);
            } else {
                $val['comment_extend'] = '';
            }
            $val['comment'] = trim($val['comment']);
            $val['comment_extend'] = trim($val['comment_extend']);

            // 刷新操作
            $oldStructData = null;
            if ($this->tempTemplatePath
                && isset($this->oldStructData[$val['field']])) {
                $oldStructData = $this->oldStructData[$val['field']];
                unset($this->oldStructData[$val['field']]);
            }

            $columnInfo = $this->parseColumnExtendData($val);

            $structData = [];
            $structData[] = <<<'EOT'
                    #[Struct([
                EOT;

            if ($val['comment']) {
                $structData[] = <<<EOT
                            self::COLUMN_NAME => '{$val['comment']}',
                    EOT;
            }

            if ($val['comment_extend']) {
                $structData[] = <<<EOT
                            self::COLUMN_COMMENT => '{$val['comment_extend']}',
                    EOT;
            }

            if ($val['primary_key']) {
                $structData[] = <<<'EOT'
                            self::READONLY => true,
                    EOT;
            }

            if (\in_array($val['field'], $showPropBlackColumn, true)) {
                $structData[] = <<<'EOT'
                            self::SHOW_PROP_BLACK => true,
                    EOT;
            }

            if ($columnInfo) {
                $structData[] = <<<EOT
                            self::COLUMN_STRUCT => [
                    {$columnInfo}
                            ],
                    EOT;
            }

            $fieldName = Camelize::handle($val['field']);
            $fieldType = $this->parseColumnType($val['type']);

            if ('string' === $fieldType
                && isset($val['type_length'])
                && $val['type_length'] > 0) {
                $structData[] = <<<EOT
                            self::COLUMN_VALIDATOR => [
                                self::VALIDATOR_SCENES => [
                                    'max_length:{$val['type_length']}',
                                ],
                                'store' => null,
                                'update' => null,
                            ],
                    EOT;
            }

            $structData[] = <<<EOT
                    ])]
                    protected ?{$fieldType} \${$fieldName} = null;

                EOT;

            $nowStructData = implode(PHP_EOL, $structData);
            if ($oldStructData && trim($oldStructData) !== trim($nowStructData)) {
                $oldStructData = str_replace('protected ?', '// protected ?', $oldStructData);
                $struct[] = $oldStructData;
            }

            $struct[] = $nowStructData;
        }

        // 刷新操作
        if ($this->tempTemplatePath) {
            foreach ($this->oldStructData as $k => $v) {
                $struct[] = $v;
            }
        }

        return trim(implode(PHP_EOL, $struct), PHP_EOL);
    }

    protected function parseColumnType(string $type): string
    {
        return match ($type) {
            'int', 'tinyint', 'smallint', 'mediumint', 'bigint', 'boolean' => 'int',
            'float', 'double' => 'float',
            default => 'string',
        };
    }

    /**
     * 获取 const 扩展信息.
     */
    protected function getConstExtend(array $columns): string
    {
        $deleteAtColumn = $this->composerConfig()['delete_at'];
        if (!$deleteAtColumn || !isset($columns['list'][$deleteAtColumn])) {
            return '';
        }

        return <<<'EOT'


                /**
                 * Soft delete column.
                 */
                public const DELETE_AT = 'delete_at';
            EOT;
    }

    /**
     * 取得应用的 composer 配置.
     */
    protected function composerConfig(): array
    {
        if (null !== $this->composerConfig) {
            return $this->composerConfig;
        }

        $path = $this->app->path().'/composer.json';
        if (!is_file($path)) {
            return $this->composerConfig = [
                'show_prop_black' => [],
                'delete_at' => null,
            ];
        }

        $config = $this->getFileContent($path);
        $config = $config['extra']['leevel-console']['database-entity'];

        return $this->composerConfig = [
            'show_prop_black' => $config['show_prop_black'] ?? [],
            'delete_at' => $config['delete_at'] ?? null,
        ];
    }

    /**
     * 获取配置信息.
     */
    protected function getFileContent(string $path): array
    {
        return (array) json_decode(file_get_contents($path) ?: '', true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * 分析字段附加信息数据.
     */
    protected function parseColumnExtendData(array $columns): string
    {
        $result = [];
        $i = 0;
        foreach ($this->normalizeColumnItem($columns) as $k => $v) {
            if (null === $v) {
                $v = 'null';
            } elseif (\is_string($v)) {
                $v = "'".trim($v)."'";
            }

            $item = "            '{$k}' => {$v},";
            ++$i;
            $result[] = $item;
        }

        return implode(PHP_EOL, $result);
    }

    /**
     * 整理数据库字段.
     *
     * - 删除掉一些必须的字段，以及调整一些展示优先级
     */
    protected function normalizeColumnItem(array $column): array
    {
        if (null !== $column['default']) {
            $column['default'] = match ($this->parseColumnType($column['type'])) {
                'int' => (int) $column['default'],
                'float' => (float) $column['default'],
                default => (string) $column['default'],
            };
        }

        $data = [
            'type' => $column['type'],
            'default' => $column['default'],
        ];

        if ($column['type_length']) {
            $data['length'] = $column['type_length'];
        }

        return $data;
    }

    /**
     * 获取数据库表名字.
     */
    protected function getTableName(): string
    {
        if ($this->getOption('table')) {
            return (string) $this->getOption('table');
        }

        return UnCamelize::handle($this->tableName);
    }

    protected function parseAppTable(): void
    {
        $appName = 'Base';
        $tableName = (string) $this->getArgument('name');
        if (str_contains($tableName, ':')) {
            [$appName, $tableName] = explode(':', $tableName);
        }

        $this->appName = $appName;
        $this->tableName = $tableName;
    }

    /**
     * 获取模板路径.
     *
     * @throws \InvalidArgumentException
     */
    protected function getStubPath(): string
    {
        if ($this->getOption('stub')) {
            $stub = (string) $this->getOption('stub');
        } else {
            $stub = __DIR__.'/stub/entity';
        }

        if (!is_file($stub)) {
            throw new \InvalidArgumentException(sprintf('Entity stub file `%s` was not found.', $stub));
        }

        return $stub;
    }

    /**
     * 获取数据库表字段信息.
     *
     * @throws \Exception
     */
    protected function getColumns(): array
    {
        $connect = $this->getOption('connect') ?: null;
        $result = $this->database
            ->connect($connect)
            ->getTableColumns($tableName = $this->getTableName(), true)
        ;
        if (empty($result['list'])) {
            throw new \Exception(sprintf('Table (%s) is not found or has no columns.', $tableName));
        }

        return $result;
    }

    /**
     * 获取数据库表唯一索引信息.
     */
    protected function getUniqueIndex(): array
    {
        $connect = $this->getOption('connect') ?: null;

        return $this->database
            ->connect($connect)
            ->getUniqueIndex($this->getTableName(), true)
        ;
    }

    /**
     * 整理数据库表唯一索引信息.
     */
    protected function parseUniqueIndex(array $uniqueIndex): string
    {
        // 构建输出字符串
        $outputString = '    /**'.PHP_EOL;
        $outputString .= '     * Unique Index.'.PHP_EOL;
        $outputString .= '     */'.PHP_EOL;
        $outputString .= '    public const UNIQUE_INDEX = ['.PHP_EOL;
        foreach ($uniqueIndex as $indexName => $indexInfo) {
            $outputString .= "        '{$indexName}' => [".PHP_EOL;
            $outputString .= "            'field' => ['".implode("', '", $indexInfo['field'])."'],".PHP_EOL;
            $outputString .= "            'comment' => '".$indexInfo['comment']."',".PHP_EOL;
            $outputString .= '        ],'.PHP_EOL;
        }
        $outputString .= '    ];'.PHP_EOL;

        return $outputString;
    }

    /**
     * 命令参数.
     */
    protected function getArguments(): array
    {
        return [
            [
                'name',
                InputArgument::REQUIRED,
                'This is the entity name.',
            ],
        ];
    }

    /**
     * 命令配置.
     */
    protected function getOptions(): array
    {
        return [
            [
                'table',
                null,
                InputOption::VALUE_OPTIONAL,
                'The database table of entity',
            ],
            [
                'stub',
                null,
                InputOption::VALUE_OPTIONAL,
                'Custom stub of entity',
            ],
            [
                'refresh',
                'r',
                InputOption::VALUE_NONE,
                'Refresh entity struct',
            ],
            [
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force update entity',
            ],
            [
                'connect',
                null,
                InputOption::VALUE_OPTIONAL,
                'The database connect of entity',
            ],
        ];
    }
}
