<?php


namespace App\CurdHelp;


use ProtoParser\StringHelp;

class GoCurd
{
    protected static $orm = [];

    public static function init()
    {
        if (!self::$orm) {
            self::$orm['controller']  = file_get_contents(__DIR__.'/template/controller');
            self::$orm['del_action']  = file_get_contents(__DIR__.'/template/del_action');
            self::$orm['get_action']  = file_get_contents(__DIR__.'/template/get_action');
            self::$orm['post_action'] = file_get_contents(__DIR__.'/template/post_action');
            self::$orm['put_action']  = file_get_contents(__DIR__.'/template/put_action');
            self::$orm['proto']       = file_get_contents(__DIR__.'/template/proto');
        }
    }

    /**
     * @param  string  $tableName
     * @param  string  $model
     * @param  string  $controller
     * @param  array  $info
     * @return array|mixed|string|string[]
     *
     * $res[$name] = [
     * 'name'       => $name,
     * 'go_type'    => $go_type,
     * 'proto_type' => $proto_type,
     * 'doc'        => $column['comment'],
     * 'proto_str'  => "// {$column['comment']}\n{$proto_type} {$name} = {$i};"
     * ];
     */
    public static function gen(
        string $tableName,
        string $model,
        string $controller,
        array $info,
        string $app = 'admin',
        string $controller_doc = ''
    ) {
        $table_info_name = self::toName($tableName)."Info";
        $table_orm       = "Orm".self::toName($tableName);

        $table_copy_api = [];
        $update_str     = [];
        foreach ($info as $arr) {
            $fieldName        = self::toName($arr['name']);
            $table_copy_api[] = "                 {$fieldName}: cp.{$fieldName}";
            $update_str[]     = "                 {$fieldName}: req.{$fieldName}";
        }

        $controllerPath = HOME_PATH.'/app/http/'.$app.'/'.$model.'/'.$tableName;
        if (!is_dir($controllerPath)) {
            mkdir($controllerPath, 0755, true);
        }
        foreach (self::$orm as $fileName => $fileStr) {
            if ($fileName == 'controller') {
                $fileName = $tableName.'_controller';
            } elseif (in_array($fileName, ['proto'])) {
                continue;
            }
            $str = str_replace(
                [
                    9 => '{app}',
                    0 => '{controller}',
                    1 => '{controller_name}',
                    2 => '{model}',
                    3 => '{table_copy_api}',
                    4 => '{table_info_name}',
                    5 => '{table_orm}',
                    6 => '{table_name}',
                    7 => '{update_str}',
                    8 => '{controller_doc}',
                ],
                [
                    9 => $app,
                    0 => $controller,
                    1 => self::toName($controller),
                    2 => $model,
                    3 => implode(",\n", $table_copy_api).',',
                    4 => $table_info_name,
                    5 => $table_orm,
                    6 => implode(",\n", $table_copy_api).',',
                    7 => '',
                    8 => $controller_doc,
                ],
                $fileStr
            );

            $fileSave = $controllerPath.'/'.$fileName.'.go';
            if (!file_exists($fileSave)) {
                file_put_contents($fileSave, $str);
            }
        }

        // proto 文件生成
        $protoStr = '';
        foreach ($info as $arr) {
            $protoStr .= "    ".$arr['proto_str']."\n";
        }
        $fileSave = HOME_PATH.'/protobuf/'.$app.'/'.$model.'/'.$tableName.'.proto';
        if (!is_dir(HOME_PATH.'/protobuf/'.$app.'/'.$model)) {
            mkdir(HOME_PATH.'/protobuf/'.$app.'/'.$model, 0755, true);
        }
        if (!file_exists($fileSave)) {
            file_put_contents($fileSave, str_replace(
                [
                    9  => '{app}',
                    0  => '{controller}',
                    1  => '{controller_name}',
                    2  => '{model}',
                    4  => '{table_info_name}',
                    5  => '{table_orm}',
                    7  => '{proto_str}',
                    8  => '{table_name}',
                    10 => '{controller_doc}',
                ],
                [
                    9  => $app,
                    0  => $controller,
                    1  => self::toName($controller),
                    2  => $model,
                    4  => $table_info_name,
                    5  => $table_orm,
                    7  => $protoStr,
                    8  => $tableName,
                    10 => $controller_doc,
                ],
                self::$orm['proto']
            ));
        }
    }


    public static function toName(string $name): string
    {
        $name = StringHelp::toCamelCase($name);
        return ucfirst($name);
    }
}