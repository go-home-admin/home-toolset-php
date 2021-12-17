<?php


namespace App\OrmHelp;


use App\Go;
use ProtoParser\StringHelp;

class OrmPgHelp
{
    protected static $orm = [];

    protected static function getOrmString(string $key): string
    {
        if (!self::$orm) {
            self::$orm['header']        = file_get_contents(__DIR__.'/template/orm');
            self::$orm['func']          = file_get_contents(__DIR__.'/template/func');
            self::$orm['with_has_one']  = file_get_contents(__DIR__.'/template/with_has_one');
            self::$orm['with_has_many'] = file_get_contents(__DIR__.'/template/with_has_many');
            self::$orm['list_alias']    = file_get_contents(__DIR__.'/template/list_alias');
            self::$orm['map']           = file_get_contents(__DIR__.'/template/map');
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
        $jsonConf  = $tableInfo['json'];

        $import    = [];
        $columnStr = '';
        foreach ($columns as $column) {
            $columnStr .= self::getColumnStr($column, $import);
        }
        // 字段映射struct
        $fieldStr = self::getFieldStr($columns);

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
        $null = [];
        // id 类型字段辅助函数生成
        foreach ($columns as $column) {
            $sourceType = self::getGoType($column, $null);
            if (strpos($sourceType, 'int') !== false) {
                $func .= "\n".self::toIntIdsMapFunc($column, $sourceType);
            }
        }

        if ($jsonConf) {
            // 关联函数生成
            foreach ($jsonConf as $confs) {
                foreach ($confs as $funName => $conf) {
                    $sourceArr = explode('.', $conf['source']);
                    if (!isset($columns[$sourceArr[1]])) {
                        echo $tableName, "表, 字段", $sourceArr[1], "不存在\n";
                        continue;
                    }
                    $sourceType = self::getGoType($columns[$sourceArr[1]], $null);

                    $otherArr = explode('.', $conf['other']);
                    switch ($conf['type']) {
                        case 'has_one':
                            $otherOrm = "*Orm".self::toName($otherArr[0]);
                            break;
                        case 'has_many':
                            $otherOrm = "[]*Orm".self::toName($otherArr[0]);
                            break;
                    }

                    $func      .= "\n".self::toHelpJsonFunc($funName, $conf, $sourceType);
                    $columnStr .= "\n{$funName} {$otherOrm}\n";
                }
            }
        }
        $func .= "\n".self::toMapFunc($columns);

        $fileStr = str_replace(
            ['{package}', '{import}', '{name}', '{column}', '{func}', '{field}'],
            [$db, $importStr, $structName, $columnStr, $func, $fieldStr],
            $fileStr
        );
        $fileStr = str_replace(['{name}', '{table}'], [$structName, $tableName], $fileStr);
        file_put_contents($file, $fileStr);
    }

    // id类型的辅助函数生成
    public static function toIntIdsMapFunc($column, $sourceType)
    {
        $fileStr = self::getOrmString('list_alias');

        return str_replace(
            ['{column_name}', '{column_type}'],
            [self::toName($column['attname']), $sourceType],
            $fileStr
        );
    }

    // id类型的辅助函数生成
    public static function toMapFunc($columns)
    {
        $fileStr = self::getOrmString('map');
        $mapWithOneStr = '';
        $mapWithManyStr = '';
        foreach ($columns as $column) {
            $mapWithOneStr .= "\n" . self::getMapWithStr($column);
            $mapWithManyStr .= "\n" . self::getMapWithStr($column, true);
        }
        return str_replace(
                ['{mapWithOneStr}', '{mapWithManyStr}'],
                [$mapWithOneStr, $mapWithManyStr],
                $fileStr
            ) . "\n";
    }

    // 关联函数生成
    public static function toHelpJsonFunc($funName, $conf, $sourceType): string
    {
        $type = $conf['type'];

        $sourceArr = explode('.', $conf['source']);
        $source    = "Orm".$sourceArr[0];
        $sourceKey = self::toName($sourceArr[1]);

        $otherArr        = explode('.', $conf['other']);
        $otherOrm        = "Orm".self::toName($otherArr[0]);
        $otherKey        = $otherArr[1];
        $otherKeyByCamel = self::toName($otherKey);
        $fileStr         = self::getOrmString('with_'.$type);

        return str_replace(
            [
                '{source}', '{source_key}', '{source_type}', '{FuncName}', '{other}', '{other_orm}', '{other_key}',
                '{other_key_by_camel}'
            ],
            [
                $source, $sourceKey, $sourceType, self::toName($funName), $funName, $otherOrm, $otherKey,
                $otherKeyByCamel
            ],
            $fileStr
        );
    }

    public static function getColumnFuncStr(array $column): string
    {
        $columnName = $column['attname'];
        $field      = self::toName($column['attname']);
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
        $name   = self::toName($column['attname']);
        $goType = self::getGoType($column, $impload);
        $type   = $column['type'];
        $key    = self::getColumnKey($column);
        $null   = self::getColumnNull($column);
        $doc    = str_replace("\n", ',', $column['comment']);

        return <<<str
    {$name} {$goType} `gorm:"{$key}column:{$column['attname']};type:{$type}{$null}"` // {$doc}\n
str;
    }

    public static function getMapWithStr(array $column, $is_many = false): string
    {
        $arr       = [];
        $name      = $column['attname'];
        $camelName = self::toName($column['attname']);
        $goType    = self::getGoType($column, $arr);

        if ($is_many) {
            $dataStr1 = "data[str] = append(data[str], item)";
            $dataStr2 = "data[item.{$camelName}] = append(data[item.{$camelName}], item)";
        } else {
            $dataStr1 = "data[str] = item";
            $dataStr2 = "data[item.{$camelName}] = item";
        }

        $str = <<<str
        case "{$name}":
str;
        switch ($goType) {
            case 'time.Time':
                $str .= "\n            str := item.{$camelName}.YmdHis()\n            {$dataStr1}";
                break;
            case 'datatypes.Date':
                $str .= "\n            str := time.Time(item.{$camelName}).Ymd()\n            {$dataStr1}";
                break;
            default:
                $str .= "\n            {$dataStr2}";
        }
        return $str;
    }

    public static function getColumnKey(array $column): string
    {
        return $column['pkey'] ? 'primaryKey;' : '';
    }

    public static function getColumnNull(array $column): string
    {
        return $column['attnotnull'] == 't' ? ';not null' : '';
    }

    public static function getGoType(array $column, array &$impload): string
    {
        $type  = $column['type'];
        $gom   = Go::getModule();
        $point = $column['attnotnull'] == 't' ? '' : '*';

        switch ($type) {
            case 'integer':
            case 'smallint':
                $got = 'int32';
                break;
            case 'bigint':
                $got = 'int64';
                break;
            case 'date':
                $got                          = $point.'datatypes.Date';
                $impload["gorm.io/datatypes"] = '"gorm.io/datatypes"';
                break;
            case 'json':
            case 'jsonb':
                $got              = $point.'pgtype.JSONB';
                $impload["jsonb"] = '"'.$gom.'/app/common/pgtype"';
                break;
            default:
                if (stripos($type, "timestamp") !== false) {
                    if ($column['attname'] == 'deleted_at') {
                        $got = $point."gorm.DeletedAt";
                    } else {
                        $got             = $point.'time.Time';
                        $impload["time"] = '"'.$gom.'/app/common/time"';
                    }
                } elseif (stripos($type, "decimal") !== false || stripos($type, "numeric",) !== false) {
                    $got = 'float32';
                } else {
                    $got = 'string';
                }
                break;
        }
        return $got;
    }

    public static function toName(string $name): string
    {
        $name = StringHelp::toCamelCase($name);
        return ucfirst($name);
    }

    public static function getFieldStr($columns): string
    {
        $str       = "{\n";
        $fieldStr1 = '';
        $fieldStr2 = '';
        foreach ($columns as $column) {
            $CamelStr  = self::toName($column['attname']);
            $fieldStr1 .= "    {$CamelStr} string\n";
            $fieldStr2 .= "    \"{$column['attname']}\",\n";
        }
        return $str.$fieldStr1."}{\n".$fieldStr2."}";
    }
}
