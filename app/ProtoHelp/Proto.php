<?php


namespace App\ProtoHelp;


use App\Go;
use GoLang\Parser\Generate\GoLangFile;
use GoLang\Parser\GolangParser;
use ProtoParser\ProtoParser;
use ProtoParser\StringHelp;

class Proto
{
    /**
     * @var ProtoParser
     */
    private $protoParser;
    private $packageProtoParser;

    public function __construct(ProtoParser $protoParser)
    {
        $this->protoParser = $protoParser;
        foreach ($this->protoParser->getAllProto() as $protoParser) {
            $this->packageProtoParser[dirname($protoParser->file)][$protoParser->file] = $protoParser;
        }
    }

    public function getAllPackage()
    {
        return $this->packageProtoParser;
    }

    public function makeRouteConfig()
    {
        $all = [];
        foreach ($this->getRouteDir() as $package => $protoFileArr) {
            $imports = [
                "home_gin" => "github.com/gin-gonic/gin",
                "home_api" => Go::getModule()."/bootstrap/http/api",
            ];
            $structs = [];
            /** @var ProtoParser $parser */
            foreach ($protoFileArr as $parser) {
                foreach ($parser->getService()->getArray() as $service) {
                    $serverName = $service->getName();
                    $alias      = StringHelp::toUnderScore($serverName);
                    $import     = Go::getModule()."/app/http/{$package}/{$alias}";
                    $opts       = $service->getOptions();

                    $structs[$alias]["group"] = ($opts["http.RouteGroup"])->getValue();
                    $structs[$alias]["name"]  = $serverName;
                    $imports[$alias]          = $import;
                    // 函数创建
                    foreach ($service->getRpc() as $rpc) {
                        foreach ($rpc->getOptions() as $type => $url) {
                            if (strpos($type, 'http.') === 0) {
                                $structs[$alias]["url"][] = [
                                    "method" => substr($type, 5),
                                    "path"   => $url,
                                    "rpc"    => $rpc,
                                ];
                            }
                        }
                    }
                }
                $all[$package] = $structs;
            }

            asort($imports);
            $package   = str_replace(['/'], ['_'], $package);
            $routeFile = HOME_PATH."/routes/{$package}_gen.go";
            $outFile   = GenCode::getRouteFile($imports, $package, $structs);
            $outGroup  = GenCode::getRouteGroup($package, $structs);
            file_put_contents($routeFile, $outFile.$outGroup);
            echo "更新路由分组 {$routeFile} ", PHP_EOL;
        }

        $routeFile = HOME_PATH."/routes/all_routes_gen.go";
        file_put_contents($routeFile, GenCode::getAllRouteGroup($all));
        echo "更新路由引用文件 {$routeFile} ", PHP_EOL;

        $outPath = dirname($routeFile);
        exec("cd {$outPath} && go fmt");
    }

    public function makeRoute()
    {
        foreach ($this->getRouteDir() as $package => $protoFileArr) {
            /** @var ProtoParser $parser */
            foreach ($protoFileArr as $parser) {
                foreach ($parser->getService()->getArray() as $service) {
                    $serverName     = $service->getName();
                    $controllerPath = HOME_PATH.'/app/http/'.$package.'/'.StringHelp::toUnderScore($service->getName());
                    $this->makeDir($controllerPath);

                    // 路由创建
                    $outControllerFile = $controllerPath.'/'.StringHelp::toUnderScore($serverName).'_controller.go';
                    if (!file_exists($outControllerFile)) {
                        $outControllerText = GenCode::getController(
                            StringHelp::toUnderScore($serverName),
                            $serverName,
                            pathinfo($outControllerFile, PATHINFO_BASENAME)
                        );
                        file_put_contents($outControllerFile, $outControllerText);
                        echo "写入控制器 {$outControllerFile} ", PHP_EOL;
                    }

                    // 函数创建
                    foreach ($service->getRpc() as $rpc) {
                        foreach ($rpc->getOptions() as $type => $option) {
                            $actionFile = $controllerPath.'/'.StringHelp::toUnderScore($rpc->getName()).'_action.go';
                            if (!file_exists($actionFile)) {
                                $outActionText = GenCode::getAction($parser, $service, $rpc, $package);
                                file_put_contents($actionFile, $outActionText);
                                echo "写入路由函数 {$actionFile} ", PHP_EOL;
                            }
                        }
                    }
                    exec("cd {$controllerPath} && git add .");
                }
            }
        }
    }

    public function getPackageWithHome(ProtoParser $parser): string
    {
        $file = $parser->file;
        $arr  = explode(HOME_PATH.'/protobuf/', $file);
        if (count($arr) == 2) {
            $file    = $arr[1];
            $file    = pathinfo($file);
            $package = $file['dirname'];
        } else {
            $package = $parser->getPackage()->getValue();;
        }

        return $package;
    }

    /**
     * 返回拥有路由信息的package列表
     * @return array
     */
    public function getRouteDir(): array
    {
        $got = [];
        foreach ($this->packageProtoParser as $arr) {
            /** @var ProtoParser $proto */
            foreach ($arr as $proto) {
                $services = $proto->getService();
                if ($services) {
                    foreach ($services->getArray() as $service) {
                        $opts = $service->getOptions();
                        if (isset($opts['http.RouteGroup'])) {
                            $filePackage                     = $this->getPackageWithHome($proto);
                            $got[$filePackage][$proto->file] = $proto;
                        }
                    }
                }
            }
        }
        return $got;
    }

    private function makeDir(string $path)
    {
        if (!is_dir($path)) {
            echo "mkdir 0755 {$path}\n";
            mkdir($path, 0755, true);
        }
    }
}