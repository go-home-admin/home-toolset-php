<?php


namespace App\ProtoHelp;

/**
 * 数据库信息生成到proto文件
 * @package App\ProtoHelp
 */
class PgSqlToProto
{
    protected $tableInfo;
    protected $model;
    protected $controller;

    /**
     * PgSqlToProto constructor.
     * @param  array  $tableInfo
     * @param  string  $model  admin/security
     * @param  string  $controller  AdminGroupMenu
     */
    public function __construct(array $tableInfo, string $model, string $controller)
    {
        $this->controller = $controller;
        $this->model      = $model;
        $this->tableInfo  = $tableInfo;
    }

    /**
     * @return array
     */
    public function gen(): array
    {
        $res = [];
        $i = 1;
        foreach ($this->tableInfo['column'] as $name => $column) {
            $go_type    = $this->getGoType($column);
            $proto_type = $this->getProtoType($column);
            $res[$name] = [
                'name'       => $name,
                'go_type'    => $go_type,
                'proto_type' => $proto_type,
                'doc'        => $column['comment'],
                'proto_str'  => "// {$column['comment']}\n    {$proto_type} {$name} = {$i};"
            ];
            $i++;
        }
        return $res;
    }

    private function getGoType(array $column): string
    {
        $type = $column['type'];

        switch ($type) {
            case 'integer':
            case 'smallint':
                $got = 'int32';
                break;
            case 'bigint':
                $got = 'int64';
                break;
            case 'bool':
                $got = 'bool';
                break;
            case 'date':
                $got = 'pgtype.Date';
                break;
            default:
                if (stripos("timestamp", $type) !== false) {
                    $got = 'string';
                } elseif (stripos("decimal", $type) !== false || stripos("numeric", $type) !== false) {
                    $got = 'float64';
                } else {
                    $got = 'string';
                }
                break;
        }
        return $got;
    }

    private function getProtoType(array $column): string
    {
        $type = $column['type'];

        switch ($type) {
            case 'integer':
            case 'smallint':
                $got = 'int32';
                break;
            case 'bigint':
                $got = 'int64';
                break;
            case 'bool':
                $got = 'bool';
                break;
            default:
                if (stripos("decimal", $type) !== false || stripos("numeric", $type) !== false) {
                    $got = 'float';
                } else {
                    $got = 'string';
                }
                break;
        }
        return $got;
    }
}
