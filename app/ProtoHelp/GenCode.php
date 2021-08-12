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
        foreach ($rpc->getOptions() as $k=>$v){
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
}