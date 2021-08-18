<?php


namespace App\Commands;


use App\Go;
use App\ProtoHelp\ProtoMethod;
use ProtoParser\DirsHelp;
use ProtoParser\FileParser\Message\Enum;
use ProtoParser\FileParser\Message\Message;
use ProtoParser\FileParser\Message\Type;
use ProtoParser\ProtoParser;
use ProtoParser\ProtoToArray;
use ProtoParser\ProtoType;
use ProtoParser\Swagger\Definition;
use ProtoParser\Swagger\Parameter;
use ProtoParser\Swagger\Path;
use ProtoParser\Swagger\ProtoMessageToSwagger;
use ProtoParser\Swagger\Response;
use ProtoParser\Swagger\Swagger;
use ProtoParser\Swagger\Tag;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SwaggerCommand extends ProtocCommand
{
    /**
     * @var OutputInterface
     */
    protected $output;

    protected function configure()
    {
        return $this->setName("make:swagger")
            ->addOption(
                "protobuf",
                null,
                InputOption::VALUE_OPTIONAL,
                "框架存放proto文件的目录, 默认 = ./protobuf"
            )
            ->addOption(
                "proto_path", null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "这个参数会在系统protoc命令原因附加"
            )
            ->setDescription("生成文档");
    }


    /**
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;

        $protocPaths  = $this->getProtobufDir($this->getProtobuf($input));
        $parser       = $this->loadProtoFile(array_merge($protocPaths, $this->getProtoPath($input)));
        $routeService = $this->loadRouteService($parser);
        $routeConfig  = $this->loadRouteConfig();
        $moduleReadme = $this->loadRouteModuleReadme($protocPaths);

        $swagger      = $this->makeSwagger();
        $moduleReadme = $this->setSwaggerPath($parser, $swagger, $routeService, $routeConfig, $moduleReadme);
        $this->setSwaggerMessage($parser, $swagger);
        $this->setSwaggerEnum($parser, $swagger);
        $this->setSwaggerTags($parser, $swagger, $moduleReadme);

        $this->makeSwaggerFile($swagger);
        return Command::SUCCESS;
    }

    protected function makeSwagger(): Swagger
    {
        $swagger         = new Swagger();
        $baseSwaggerFile = HOME_PATH."/swagger.json";
        if (file_exists($baseSwaggerFile)) {
            $str = file_get_contents($baseSwaggerFile);
            $arr = json_decode($str, true);
            isset($arr["swagger"]) and $swagger->setSwagger($arr["swagger"]);
            isset($arr["info"]) and $swagger->setInfo($arr["info"]);
            isset($arr["host"]) and $swagger->setHost($arr["host"]);
            isset($arr["basePath"]) and $swagger->setBasePath($arr["basePath"]);
            isset($arr["schemes"]) and $swagger->setSchemes($arr["schemes"]);
            isset($arr["paths"]) and $swagger->setPaths($arr["paths"]);
            isset($arr["definitions"]) and $swagger->setDefinitions($arr["definitions"]);
            isset($arr["securityDefinitions"]) and $swagger->setSecurityDefinitions($arr["securityDefinitions"]);
            isset($arr["externalDocs"]) and $swagger->setExternalDocs($arr["externalDocs"]);
        }

        return $swagger;
    }

    protected function setSwaggerTags(ProtoParser $parser, Swagger $swagger, array $moduleReadme)
    {
        foreach ($moduleReadme as $routeModule => $doc) {
            $tag = new Tag();
            $tag->setName($routeModule);
            $tag->setDescription($doc);
            $swagger->addTag($tag);
        }
    }

    // 所有message
    protected function setSwaggerMessage(ProtoParser $parser, Swagger $swagger)
    {
        foreach ($parser->getMessageWithAll() as $arr) {
            /** @var \ProtoParser\FileParser\Message\Message $message */
            list($packageName, $message) = $arr;
            $messageName = $this->toModuleName($packageName, $message->getName());
            $message->setName($messageName);
            $definition = $this->toDefinition($packageName, $message, $swagger);
            $swagger->addDefinition($definition);
        }
    }

    // 所有Enum
    protected function setSwaggerEnum(ProtoParser $parser, Swagger $swagger)
    {
        foreach ($parser->getEnumWithAll() as $arr) {
            /** @var \ProtoParser\FileParser\Message\Enum $message */
            list($packageName, $message) = $arr;
            $messageName = $this->toModuleName($packageName, $message->getName());
            $message->setName($messageName);
            $definition = $this->enumToDefinition($message);
            $swagger->addDefinition($definition);
        }
    }

    protected function setSwaggerPath(
        ProtoParser $parser,
        Swagger $swagger,
        array $routeService,
        array $routeConfig,
        array $moduleReadme
    ): array {
        $useModuleReadme = [];
        foreach ($routeService as $routeModule => $arr) {
            foreach ($arr as $routeGroup => $routeInfoArr) {
                if (!isset($routeConfig[$routeGroup])) {
                    $this->output->writeln("<error>{$routeModule}@{$routeGroup}分组信息没有配置到route.json</error>");
                    continue;
                }
                $config   = $routeConfig[$routeGroup];
                $prefix   = $config["prefix"] ?? "";
                $security = $config["security"] ?? "";

                foreach ($routeInfoArr as $routeInfo) {
                    /** @var \ProtoParser\FileParser\Service\Rpc $rpc */
                    $rpc    = $routeInfo["rpc"];
                    $url    = $routeInfo["url"];
                    $method = $routeInfo["method"];

                    $path = new Path();
                    $path->setSummary(explode("//", $rpc->getDoc())[0]);
                    $path->setMethod($method);
                    $path->setKey($prefix.$url);
                    $pathDoc = str_replace("//", "\n\n", $rpc->getDoc());
                    if ($pathDoc) {
                        $path->setDescription($pathDoc);
                    } else {
                        $path->setDescription($url);
                    }
                    $path->setTags([$routeModule => $prefix]);
                    $useModuleReadme[$routeModule] = $moduleReadme[$routeModule];

                    $message = $parser->getMessageWithAll($rpc->getParameter());
                    switch ($method) {
                        case "post":
                            $parameters = $this->toPostParameter($routeModule, $message);
                            break;
                        default:
                            $parameters = $this->toGetParameter($routeModule, $message);
                            break;
                    }
                    $path->setParameters($parameters);

                    $message   = $parser->getMessageWithAll($rpc->getResponse());
                    $responses = $this->toResponse($routeModule, $message);
                    $path->setResponses(['200' => $responses]);

                    if (is_array($security)) {
                        $path->setSecurity($security);
                    }

                    $swagger->addPath($path);
                }
            }
        }
        return $useModuleReadme;
    }

    protected function loadRouteModuleReadme(array $dirs): array
    {
        $got = [];
        foreach ($dirs as $dir) {
            $basename = pathinfo($dir, PATHINFO_BASENAME);
            $file     = $dir.'/README.md';
            if (file_exists($file)) {
                $str = file_get_contents($file);
            } else {
                $str = "这是默认说明。<br>模块详细说明写到<br>{$file}";
            }
            $got[$basename] = $str;
        }
        return $got;
    }

    protected function loadRouteConfig(): array
    {
        $file = HOME_PATH.'/route.json';
        if (!file_exists($file)) {
            return [];
        }
        $str = file_get_contents($file);
        $arr = json_decode($str, true);
        if (is_array($arr)) {
            $got = [];
            foreach ($arr as $con) {
                $got[$con["name"]] = $con;
            }
            return $got;
        }
        return [];
    }

    // 收集url
    protected function loadRouteService(ProtoParser $parser): array
    {
        $all = [];
        foreach ($parser->getAllProto() as $proto) {
            $services = $proto->getService();
            if (!$services) {
                continue;
            }

            $routeModule = $proto->getPackage()->getValue();
            foreach ($services->getArray() as $service) {
                $options = $service->getOptions();
                foreach ($options as $optionKey => $optionValue) {
                    if ("http.RouteGroup" == $optionKey) {
                        $paths = $service->getRpc();
                        foreach ($paths as $rpc) {
                            $rpcOptions = $rpc->getOptions();
                            foreach ($rpcOptions as $rpcOptionKey => $rpcUrl) {
                                if (in_array($rpcOptionKey, ProtoMethod::$allHttpMethod)) {
                                    $all[$routeModule][$optionValue->getValue()][] = [
                                        'url'    => $rpcUrl,
                                        'method' => strtolower(substr($rpcOptionKey, 5)),
                                        'rpc'    => $rpc
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $all;
    }

    // 加载所有
    protected function loadProtoFile(array $dirs): ProtoParser
    {
        $fileCache = [];
        $parser    = new ProtoParser();
        foreach ($dirs as $dir) {
            foreach (DirsHelp::getDirs($dir, 'proto') as $file) {
                $file = realpath($file);
                if (isset($fileCache[$file])) {
                    continue;
                }
                $content     = file_get_contents($file);
                $parserToArr = new ProtoToArray($content);
                $parser->parser($parserToArr, $file);

                $fileCache[$file] = true;
            }
        }

        return $parser;
    }

    protected function makeSwaggerFile(Swagger $swagger)
    {
        $json = json_encode($swagger->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents('/Users/lv/Desktop/golang/lrs-circle-server/bin/proto-parser/test/web'."/swagger.json",
            $json);
        //file_put_contents($this->getHomePath("/generate")."/swagger.json", $json);
    }

    protected function toResponse(string $routeModule, Message $message): Response
    {
        $response    = new Response();
        $messageName = $this->toModuleName($routeModule, $message->getName());
        $response->setSchema([
            "\$ref" => "#/definitions/{$messageName}"
        ]);
        $response->setDescription($message->getDoc());
        return $response;
    }

    // 枚举生成定义
    public function enumToDefinition(Enum $message): Definition
    {
        $definition = new Definition();
        $definition->setName($message->getName());

        $pars = [];
        foreach ($message->getValues() as $value => $arr) {
            $doc               = str_replace(['//', '"', ' '], '', $arr["doc"]);
            $pars[$arr["num"]] = [
                "type"        => "integer",
                "description" => "{$doc} {$arr['name']}",
                "example"     => "{$doc} {$arr['name']}",
            ];
        }
        if (!isset($pars[0])) {
            $pars[0] = [
                "type"    => "integer",
                "example" => "枚举值0, 预留不做使用",
            ];
        }

        $definition->setType("object");
        $definition->setProperties($pars);
        $definition->setDescription("枚举对象:".$message->getDoc());
        return $definition;
    }

    // 对象解析出swagger的具体配置格式
    public function toDefinition(string $routeModule, Message $message, Swagger $swagger = null): Definition
    {
        $definition = new Definition();
        $definition->setName($message->getName());
        $pars = [];
        foreach ($message->getValues() as $type) {
            if ($type instanceof Message) {
                $this->output->writeln("<waring>内嵌式message一般不用, 入口或者出口的结构通常多个模块使用</waring>");
                foreach ($type->getValues() as $type2) {
                    $mType = $type2->getBType();
                    if ($mType == $type2::Base) {
                        $pars2[$type2->getName()] = [
                            "type"        => ProtoMessageToSwagger::toSwaggerType($type2),
                            "format"      => $type2->getType(),
                            "description" => $type2->getDoc(),
                        ];
                    } elseif ($mType & $type2::bTypeArray) {
                        // 数组
                        $lineType = $type2->getType();
                        if ("google.protobuf.Any" == $lineType) {
                            $lineType = "Null";
                        } else {
                            $lineType = ProtoMessageToSwagger::toSwaggerType($type2);
                        }
                        $pars2[$type2->getName()] = [
                            "type"        => "array",
                            "description" => $type2->getDoc(),
                            "items"       => [
                                "\$ref" => "#/definitions/{$lineType}"
                            ],
                        ];
                    } elseif ($mType & $type2::bTypeObject) {
                        // 其他对象引用
                        $lineType = $type2->getType();
                        if ("google.protobuf.Any" == $lineType) {
                            $lineType = "Null";
                        }
                        $pars2[$type2->getName()] = [
                            "\$ref" => "#/definitions/{$lineType}"
                        ];
                    }
                }
                $definition2Name = $message->getName()."_".$type->getName();
                $definition2     = new Definition();
                $definition2->setName($definition2Name);
                $definition2->setProperties($pars2 ?? []);
                $definition2->setDescription($type->getDoc());
                $swagger->addDefinition($definition2);

                $pars[$type->getName()] = [
                    "\$ref" => "#/definitions/{$definition2Name}"
                ];
            } else {
                $mType = $type->getBType();
                if ($mType == $type::Base) {
                    $pars[$type->getName()] = [
                        "type"        => ProtoMessageToSwagger::toSwaggerType($type),
                        "format"      => $type->getType(),
                        "description" => $type->getDoc(),
                    ];
                } elseif ($mType & $type::bTypeArray) {
                    // 数组
                    $lineType = $type->getType();
                    if (in_array($lineType, ProtoType::ALl)) {
                        $lineType               = ProtoMessageToSwagger::toSwaggerType($type);
                        $pars[$type->getName()] = [
                            "type"        => "array",
                            "description" => $type->getDoc(),
                            "items"       => [
                                "type" => $lineType,
                            ],
                        ];
                    } else {
                        if ("google.protobuf.Any" == $lineType) {
                            $messageName = "Null";
                        } else {
                            $messageName = $this->toModuleName($routeModule, $lineType);
                        }
                        $pars[$type->getName()] = [
                            "type"        => "array",
                            "description" => $type->getDoc(),
                            "items"       => [
                                "\$ref" => "#/definitions/{$messageName}"
                            ],
                        ];
                    }
                } elseif ($mType & $type::bTypeObject) {
                    // 其他对象引用
                    $lineType               = $type->getType();
                    $messageName            = $this->toModuleName($routeModule, $lineType);
                    $pars[$type->getName()] = [
                        "\$ref" => "#/definitions/{$messageName}"
                    ];
                }
            }
        }
        $definition->setDescription($message->getDoc());
        $definition->setProperties($pars);
        return $definition;
    }

    protected function toGetParameter(string $routeModule, Message $message): array
    {
        $got = [];
        // 写到url上
        foreach ($message->getValues() as $type) {
            $parameter = new Parameter();
            // 其他对象引用
            $parameter->setName($message->getName());
            $parameter->setDescription($message->getDoc());
            $bType = $type->getBType();
            if ($bType == $type::Base) {
                // proto自带类型
                $parameter->setName($type->getName());
                $parameter->setDescription($type->getDoc());
                $parameter->setFormat($type->getType());
                $parameter->setType(ProtoMessageToSwagger::toSwaggerType($type));
            } elseif ($bType & $type::bTypeObject) {
                // 其他对象引用
                $lineType = $type->getType();
                $parameter->setName($type->getName());
                $parameter->setDescription($type->getDoc());

                if (in_array($lineType, ProtoType::ALl)) {
                    $lineType = ProtoMessageToSwagger::toSwaggerType($type);
                    $parameter->setType("array");
                    $parameter->setItems([
                        "type" => $lineType,
                    ]);
                } else {
                    if ("google.protobuf.Any" == $lineType) {
                        $messageName = "Null";
                    } else {
                        $messageName = $this->toModuleName($routeModule, $lineType);
                    }
                    $parameter->setSchema([
                        "\$ref" => "#/definitions/{$messageName}"
                    ]);
                }
            }
            $parameter->setIn("query");
            $got[] = $parameter;
        }
        return $got;
    }

    // 其他对象引用
    protected function toPostParameter(string $routeModule, Message $message): array
    {
        $parameter = new Parameter();
        $parameter->setName($message->getName());
        $parameter->setDescription($message->getDoc());
        $messageName = $this->toModuleName($routeModule, $message->getName());
        $parameter->setSchema([
            "\$ref" => "#/definitions/{$messageName}"
        ]);
        $parameter->setIn("body");
        return [$parameter];
    }

    protected function toModuleName(string $routeModule, string $messageName): string
    {
        if ("google.protobuf.Any" == $messageName) {
            $messageName = "Null";
        } else {
            if (count(explode('.', $messageName)) <= 1) {
                $messageName = "{$routeModule}.".$messageName;
            }
        }

        return $messageName;
    }
}