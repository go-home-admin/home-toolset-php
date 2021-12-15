<?php


namespace App\ProtoHelp;

use App\OrmHelp\Mysql;

/**
 * 数据库信息生成到proto文件
 * @package App\ProtoHelp
 */
class MysqlToProto extends Mysql
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
        $res = $temp = [];
        $i   = 1;
        foreach ($this->tableInfo['column'] as $name => $column) {
            $go_type    = $this->getGoType($column, $temp);
            $proto_type = $this->getProtoType($column);
            $doc        = $column['COLUMN_COMMENT'] ?? "";
            $res[$name] = [
                'name'       => $name,
                'go_type'    => $go_type,
                'proto_type' => $proto_type,
                'doc'        => $doc,
                'proto_str'  => "// {$doc}\n    {$proto_type} {$name} = {$i};"
            ];
            $i++;
        }
        return $res;
    }

    private function getProtoType(array $column): string
    {
        $type = $column['DATA_TYPE'];

        switch ($type) {
            case 'int':
            case 'tinyint':
                $got = 'int32';
                break;
            case 'bigint':
                $got = 'int64';
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
