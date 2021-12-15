<?php


namespace App\OrmHelp;


use ProtoParser\StringHelp;

class Mysql
{
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
        if (strpos($column['COLUMN_TYPE'], 'unsigned')) {
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
        if (!in_array($got , ["float64", "float32"])) {
            if (strpos($column['COLUMN_TYPE'], 'unsigned')) {
                $got = 'u'.$got;
            }
        }

        return $got;
    }

    public static function toName(string $name): string
    {
        $name = StringHelp::toCamelCase($name);
        return ucfirst($name);
    }
}