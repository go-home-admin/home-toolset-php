<?php


namespace App\ProtoHelp;


use ProtoParser\FileParser\Service\Rpc;
use ProtoParser\FileParser\Service\Service;
use ProtoParser\ProtoParser;
use ProtoParser\StringHelp;

class GenCode
{
    private static $controller;

    private static $action;
    private static $routeFile;
    private static $routeGroup;

    private static $allRoutes;

    public static function getController(string $package, string $name, string $file): string
    {
        if (!self::$controller) {
            self::$controller = file_get_contents(__DIR__.'/template/controller');
        }

        return str_replace(['{package}', '{name}', '{filename}'], [$package, $name, $file], self::$controller);
    }

    public static function getAction(ProtoParser $parser, Service $server, Rpc $rpc): string
    {
        if (!self::$action) {
            self::$action = file_get_contents(__DIR__.'/template/action');
        }

        $package = StringHelp::toUnderScore($server->getName());
        $service = $server->getName();
        $name    = $rpc->getName();
        $doc     = $rpc->getDoc();

        $routeHelp = '';
        foreach ($rpc->getOptions() as $k => $v) {
            $routeHelp .= "{$k}({$v})";
        }
        return str_replace(
            [
                '{package}', '{service}', '{name}', '{doc}', '{proto_package}', '{req}',
                '{resp}', '{route_help}'
            ],
            [
                $package, $service, $name, $doc, $parser->getPackage()->getValue(), $rpc->getParameter(),
                $rpc->getResponse(), $routeHelp
            ],
            self::$action
        );
    }

    public static function getRouteFile(array $imports, string $package, array $structs): string
    {
        if (!self::$routeFile) {
            self::$routeFile = file_get_contents(__DIR__.'/template/route');
        }

        $importStr = $controllers = '';
        foreach ($imports as $alias => $importPath) {
            $importStr .= "\n\t{$alias} \"{$importPath}\"";
        }
        if ($importStr) {
            $importStr .= "\n";
        }
        foreach ($structs as $alias => $struct) {
            $controllers .= "\n\t{$alias} *{$alias}.Controller `inject:\"\"`";
        }
        if ($controllers) {
            $controllers .= "\n";
        }

        return str_replace(
            [
                '{import}',
                '{-package-}',
                '{controllers}',
            ],
            [
                $importStr,
                ucfirst($package),
                $controllers,
            ],
            self::$routeFile
        );
    }

    public static function getRouteGroup(string $package, array $structs): string
    {
        if (!self::$routeGroup) {
            self::$routeGroup = file_get_contents(__DIR__.'/template/route_group');
        }

        $groupFunc = [];
        foreach ($structs as $alias => $struct) {
            $groupName = ucfirst($struct["group"]);

            if (!isset($groupFunc[$groupName])) {
                $groupFunc[$groupName] = '';
            }
            foreach ($struct["url"] as $item) {
                /** @var Rpc $rpc */
                $rpc                   = $item['rpc'];
                $groupFunc[$groupName] .= "\n\t\thome_api.{$item['method']}(\"{$item['path']}\"): c.{$alias}.GinHandle{$rpc->getName()},";
            }
            if ($groupFunc[$groupName]) {
                $groupFunc[$groupName] .= "\n\t";
            }
        }
        $str    = '';
        $search = ['{group}', '{-package-}', '{route}'];
        foreach ($groupFunc as $group => $route) {
            $func = str_replace($search, [self::getGroupName($group), ucfirst($package), $route], self::$routeGroup);
            $str  .= "\n\n".$func;
        }

        return $str;
    }


    public static function getAllRouteGroup(array $all): string
    {
        if (!self::$allRoutes) {
            self::$allRoutes = file_get_contents(__DIR__.'/template/all_routes');
        }

        $controllers = $route = '';
        $groupFunc = [];
        foreach ($all as $package => $structs) {
            $packageUc = ucfirst($package).'Routes';
            $controllers .= "\n\t{$packageUc} *{$packageUc} `inject:\"\"`";
            foreach ($structs as $alias => $struct) {
                $group = $struct["group"];
                $groupName = self::getGroupName($group);

                if (!isset($groupFunc[$group])) {
                    // $groupFunc[$group] = "r.mergerRouteMap(),";
                    $groupFunc[$group][] = "r.{$packageUc}.Get{$groupName}Routes()";
                }
            }
        }
        if ($controllers) {
            $controllers .= "\n";
        }
        foreach ($groupFunc as $group=>$arr){
            $str = 'route_help.MergerRouteMap(';
            foreach ($arr as $m){
                $str .= "\n\t\t\t{$m},";
            }
            $str .= "\n\t\t),";
            $route .= "\n\t\t\"{$group}\": {$str}";
        }
        if ($route) {
            $route .= "\n\t";
        }
        $search = ['{controller}', '{group_map}'];

        return str_replace($search, [$controllers, $route], self::$allRoutes);
    }

    private static function getGroupName(string $group):string
    {
        $group = str_replace('-','_',$group);
        return StringHelp::toCamelCase(ucfirst($group));
    }
}