<?php


namespace App\ProtoHelp;


use App\Go;
use ProtoParser\ProtoParser;

class MakeGrpc
{
    /**
     * 生成proto文件
     * @param  \ProtoParser\ProtoParser  $parser
     * @return string
     */
    public static function makeProtoFile(ProtoParser $parser): string
    {
        $file     = $parser->file;
        $fileName = pathinfo($file, PATHINFO_BASENAME);
        $opt      = self::getOpt($file);
        $package  = $parser->getPackage()->getValue();
        $module   = Go::getModule();
        $services = $parser->getService();
        if (!$services) {
            return "";
        }

        $nl = PHP_EOL.PHP_EOL;

        $grpcStr = '// form '.__FILE__.PHP_EOL;
        $grpcStr .= 'syntax = "proto3";'.$nl;
        $grpcStr .= "package {$package}_grpc;".$nl;
        $grpcStr .= "import \"{$fileName}\";".$nl;

        $imps = $parser->getImport();
        if ($imps) {
            foreach ($imps->get() as $imp) {
                if ($imp != 'http_config.proto') {
                    $grpcStr .= "import \"{$imp}\";".$nl;
                }
            }
        }

        $grpcStr .= "option go_package = \"{$module}/generate/proto/grpc/{$opt}_grpc\";".$nl;

        $serviceArr = $services->getArray();
        if ($serviceArr) {
            foreach ($services->getArray() as $service) {
                $serviceName = $service->getName();
                $grpcStr     .= "service {$serviceName}Grpc {".PHP_EOL;
                foreach ($service->getRpc() as $rpc) {
                    $grpcStr .= "	rpc {$rpc->getName()}({$package}.{$rpc->getParameter()}) returns ({$serviceName}{$rpc->getResponse()}Grpc){".PHP_EOL;
                    $grpcStr .= "	    // ".PHP_EOL;
                    $grpcStr .= "	}".PHP_EOL;
                }
                $grpcStr .= "}".$nl;
            }

            $hasArr = [];
            foreach ($services->getArray() as $service) {
                $serviceName = $service->getName();
                foreach ($service->getRpc() as $rpc) {
                    $resName = $serviceName.$rpc->getResponse();

                    if (!isset($hasArr[$resName])) {
                        $grpcStr .= "message {$resName}Grpc {".PHP_EOL;
                        $grpcStr .= "	int32 code = 1;".PHP_EOL;
                        $grpcStr .= "	string message = 2;".PHP_EOL;
                        $grpcStr .= "	{$package}.{$rpc->getResponse()} data = 3;".PHP_EOL;
                        $grpcStr .= "}".$nl;

                        $hasArr[$resName] = true;
                    }
                }
            }
            $dir = HOME_PATH.'/generate/grpc/'.$opt;
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir.'/grpc_'.$fileName, $grpcStr);
            return $dir;
        }

        return "";
    }

    /**
     * @param  string  $file
     * @return string
     */
    public static function getOpt(string $file): string
    {
        $arr = explode(HOME_PATH.'/protobuf/', dirname($file));
        return end($arr);
    }

    /**
     * 生成go服务文件
     * @param  \ProtoParser\ProtoParser  $parser
     */
    public static function makeGrpcService(ProtoParser $parser)
    {
        $fileName    = pathinfo($parser->file, PATHINFO_FILENAME);
        $module      = Go::getModule();
        $apiPackage  = self::getOpt($parser->file);
        $str         = self::getGrpcFileString();
        $package     = $parser->getPackage()->getValue();

        $struct   = '';
        $services = $parser->getService();
        if (!$services) {
            return "";
        }
        $controller = pathinfo($parser->file, PATHINFO_FILENAME);
        foreach ($services->getArray() as $service) {
            $ControllerName = $service->getName();
            $struct         .= "// @Bean\ntype {$ControllerName} struct {\n\tcontroller *{$controller}.Controller `inject:\"\"`\n}\n";

            foreach ($service->getRpc() as $rpc) {
                $struct .= str_replace(
                    [
                        1 => '{ControllerName}',
                        2 => '{rpc}',
                        3 => '{api}',
                        4 => '{rpcReq}',
                        5 => '{package}',
                        6 => '{rpcResp}',
                    ],
                    [
                        1 => $ControllerName,
                        2 => $rpc->getName(),
                        3 => $package,
                        4 => $rpc->getParameter(),
                        5 => $apiPackage,
                        6 => $ControllerName . $rpc->getResponse(),
                    ],
                    self::getGrpcFuncFileString()
                );
            }
        }

        $fileStr = str_replace(
            [
                1 => '{package}',
                2 => '{module}',
                3 => "{api_package}",
                4 => "{controller}",
                5 => '{struct}',
            ],
            [
                1 => $package,
                2 => $module,
                3 => $apiPackage,
                4 => $controller,
                5 => $struct,
            ],
            $str
        );

        $fileName = HOME_PATH."/app/grpc/handle/{$apiPackage}/{$fileName}.go";
        if (!is_dir(dirname($fileName))) {
            mkdir(dirname($fileName), 0755, true);
        }
        file_put_contents($fileName, $fileStr);
    }

    private static $grpcFileString;

    private static function getGrpcFileString(): string
    {
        if (!self::$grpcFileString) {
            self::$grpcFileString = file_get_contents(__DIR__.'/template/grpc_handle');
        }
        return self::$grpcFileString;
    }

    private static $grpcFuncFileString;

    private static function getGrpcFuncFileString(): string
    {
        if (!self::$grpcFuncFileString) {
            self::$grpcFuncFileString = file_get_contents(__DIR__.'/template/grpc_func');
        }
        return self::$grpcFuncFileString;
    }
}