<?php


namespace App\OrmHelp;


use ProtoParser\StringHelp;

class OrmHelp extends Mysql
{
    protected static $orm = [];

    protected static function getOrmString(string $key): string
    {
        if (!self::$orm) {
            self::$orm['header'] = file_get_contents(__DIR__.'/template/orm');
            self::$orm['func']   = file_get_contents(__DIR__.'/template/func');
        }
        return self::$orm[$key];
    }

    public static function getDBConfig(string $dbname): string
    {
        $str = file_get_contents(__DIR__.'/template/config_db');
        return str_replace('{package}', $dbname, $str);
    }

    public static function gen(array $tableInfo, string $file)
    {
        $tableName = $tableInfo['name'];
        $db        = $tableInfo['db'];
        $columns   = $tableInfo['column'];

        $import    = [];
        $columnStr = '';
        foreach ($columns as $column) {
            $columnStr .= self::getColumnStr($column, $import);
        }

        $structName = 'Orm'.self::toName($tableName);
        $fileStr    = self::getOrmString('header');

        // 表结构
        $importStr = '';
        if ($import) {
            $importStr = "    ".implode("\n    ", $import);
            $importStr = "\n{$importStr}\n";
        }
        // orm函数
        $func = '';
        foreach ($columns as $column) {
            $func .= "\n".self::getColumnFuncStr($column);
        }

        $fileStr = str_replace(
            ['{package}', '{import}', '{name}', '{column}', '{func}'],
            [$db, $importStr, $structName, $columnStr, $func],
            $fileStr
        );
        $fileStr = str_replace(['{name}', '{table}'], [$structName, $tableName], $fileStr);
        file_put_contents($file, $fileStr);
    }

    public static function getColumnFuncStr(array $column): string
    {
        $columnName = $column['COLUMN_NAME'];
        $field      = self::toName($column['COLUMN_NAME']);
        $fileStr    = self::getOrmString('func');
        $import     = [];
        return str_replace(
            ['{field}', '{column}', '{type}'],
            [$field, $columnName, self::getGoType($column, $import)],
            $fileStr
        );
    }

    public static function getColumnStr(array $column, array &$impload): string
    {
        $name   = self::toName($column['COLUMN_NAME']);
        $goType = self::getGoType($column, $impload);
        $type   = self::getMysqlType($column);
        $key    = self::getColumnKey($column);
        $null   = self::getColumnNull($column);
        $doc    = str_replace("\n", ',', $column['COLUMN_COMMENT']);

        return <<<str
    {$name} {$goType} `gorm:"{$key}column:{$column['COLUMN_NAME']};type:{$type}{$null}"` // {$doc}\n
str;
    }
}
