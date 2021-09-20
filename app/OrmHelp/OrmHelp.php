<?php


namespace App\OrmHelp;


use ProtoParser\StringHelp;

class OrmHelp
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
        $structName = $tableInfo['name'];
        $db         = $tableInfo['db'];
        $columns    = $tableInfo['column'];

        $import    = [];
        $columnStr = '';
        foreach ($columns as $column) {
            $columnStr .= self::getColumnStr($column, $import);
        }

        $structName = self::toName($structName);
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
            $func .= "\n".self::getColumnFuncStr($column, $structName);
        }

        $fileStr = str_replace(
            ['{package}', '{import}', '{name}', '{column}', '{func}'],
            [$db, $importStr, $structName, $columnStr, $func],
            $fileStr
        );
        file_put_contents($file, $fileStr);
    }

    public static function getColumnFuncStr(array $column, string $structName): string
    {
        $columnName = $column['COLUMN_NAME'];
        $field      = self::toName($column['COLUMN_NAME']);
        $fileStr    = self::getOrmString('func');
        $import     = [];
        return str_replace(
            ['{name}', '{field}', '{column}', '{type}'],
            [$structName, $field, $columnName, self::getGoType($column, $import)],
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

    public static function getColumnKey(array $column): string
    {
        switch ($column['COLUMN_KEY']) {
            case 'PRI':
                $got = 'primaryKey;';
                break;
            default:
                $got = '';
                break;
        }
        return $got;
    }

    public static function getColumnNull(array $column): string
    {
        switch ($column['IS_NULLABLE']) {
            case 'NO':
                $got = ';not null';
                break;
            default:
                $got = '';
                break;
        }
        return $got;
    }

    public static function getMysqlType(array $column): string
    {
        switch ($column['DATA_TYPE']) {
            case 'timestamp':
                $got = 'timestamp';
                break;
            case 'char':
            case 'varchar':
            case 'longtext':
            case 'decimal':
                $got = $column['COLUMN_TYPE'];
                break;
            default:
                $got = "{$column['DATA_TYPE']}({$column['NUMERIC_PRECISION']})";
                break;
        }
        if (strpos('unsigned', $column['COLUMN_TYPE'])) {
            $got = $got.' unsigned';
        }
        return $got;
    }

    public static function getGoType(array $column, array &$impload): string
    {
        $type    = $column['DATA_TYPE'];
        $colType = $column['COLUMN_TYPE'];

        switch ($type) {
            case 'int':
            case 'tinyint':
                $got = 'int32';
                break;
            case 'bigint':
                $got = 'int64';
                break;
            case 'timestamp':
                $got             = 'time.Time';
                $impload["time"] = '"time"';
                break;
            case 'date':
                $got                          = 'datatypes.Date';
                $impload["gorm.io/datatypes"] = '"gorm.io/datatypes"';
                break;
            case 'decimal':
                $got = 'float64';
                break;
            default:
                $got = 'string';
                break;
        }
        if (strpos('unsigned', $colType)) {
            $got = 'u'.$got;
        }

        return $got;
    }

    public static function toName(string $name): string
    {
        $name = StringHelp::toCamelCase($name);
        return ucfirst($name);
    }
}