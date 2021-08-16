<?php


namespace App\Commands;


use App\Go;
use ProtoParser\DirsHelp;
use ProtoParser\ProtoParser;
use ProtoParser\ProtoToArray;
use ProtoParser\Swagger\Parameter;
use ProtoParser\Swagger\ProtoMessageToSwagger;
use ProtoParser\Swagger\Swagger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SwaggerCommand extends ProtocCommand
{
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
        $module      = Go::getModule();
        $protobuf    = $this->getProtobuf($input);
        $protocPaths = $this->getProtobufDir($protobuf);

        $parser    = new ProtoParser();
        $swagger   = new Swagger();
        $makeToken = false;
        foreach ($protocPaths as $dir) {
            echo($dir), "\n";
            foreach (DirsHelp::getDirs($dir, 'proto') as $file) {
                $file        = realpath($file);
                $content     = file_get_contents($file);
                $parserToArr = new ProtoToArray($content);
                $parser->parser($parserToArr, $file);
            }
        }

        foreach ($parser->getAllProto() as $proto) {
            $services = $proto->getService();
            if (!$services) {
                continue;
            }

            // 按路由组划分
            $servicesTag = 'home';
            foreach ($services->getArray() as $serviceName => $service) {
                $options = $service->getOptions();
                foreach ($options as $optionKey => $optionValue) {
                    if ("http.Route" == $optionKey) {
                        $tag = new \ProtoParser\Swagger\Tag();
                        $tag->setName($optionValue->getValue());
                        $tag->setDescription($optionValue->getDoc());

                        $servicesTag = $optionValue->getValue();
                        if (!isset($tags[$tag->getName()])) {
                            $tags[$tag->getName()] = ["base" => "/"];
                            $swagger->addTag($tag);
                        }
                    }
                }
            }
            // 所有路由
            foreach ($services->getArray() as $serviceName => $service) {
                $paths = $service->getRpc();
                foreach ($paths as $key => $rpc) {
                    $options = $rpc->getOptions();
                    foreach ($options as $optionKey => $optionValue) {
                        $optionKeyArr = explode('.', $optionKey);
                        if (count($optionKeyArr) == 2 && $optionKeyArr[0] == "http") {
                            $method = strtolower($optionKeyArr[1]);
                            $path   = new \ProtoParser\Swagger\Path();
                            $path->setSummary(explode("//", $rpc->getDoc())[0]);
                            $path->setMethod($method);
                            $path->setKey(($tags[$servicesTag]['base'] ?? "").$optionValue);
                            $path->setDescription(str_replace("//", "\n\n", $rpc->getDoc()));
                            $path->setTags([$servicesTag => ($tags[$servicesTag]['base'] ?? "/")]);

                            $parameters = [];
                            $message    = $rpc->getParameter();
                            $message    = $parser->getMessageWithAll($message);
                            if ($message) {
                                $parameters = ProtoMessageToSwagger::toParameter($message, $method);

                                if ($makeToken && isset($tags[$servicesTag]['security'])) {
                                    $security  = key($tags[$servicesTag]['security']);
                                    $parameter = new Parameter();
                                    $parameter->setName($security);
                                    $parameter->setType("string");
                                    $parameter->setDefault("{{".$security."}}");
                                    $parameter->setIn("query");
                                    $parameters[] = $parameter;
                                }
                            }
                            $path->setParameters($parameters);

                            $responses = [];
                            $message   = $rpc->getResponse();
                            $message   = $parser->getMessageWithAll($message);
                            if ($message) {
                                $responses = ProtoMessageToSwagger::toResponse($message);
                            }
                            $path->setResponses(['200' => $responses]);

                            if (isset($tags[$servicesTag]['security'])) {
                                $path->setSecurity([$tags[$servicesTag]['security']]);
                            }
                            $swagger->addPath($path);
                        }
                    }
                }
            }
        }

        // 所有message
        foreach ($parser->getMessageWithAll() as $arr) {
            /** @var \ProtoParser\FileParser\Message\Message $message */
            list($packageName, $message) = $arr;

            if (in_array($packageName, ["common"])) {
                $message->setName("{$packageName}.{$message->getName()}");
            }
            $definition = ProtoMessageToSwagger::toDefinition($message, $swagger);
            $swagger->addDefinition($definition);
        }
        // 所有Enum
        foreach ($parser->getEnumWithAll() as $arr) {
            /** @var \ProtoParser\FileParser\Message\Enum $message */
            list($packageName, $message) = $arr;

            if (in_array($packageName, ["common"])) {
                $message->setName("{$packageName}.{$message->getName()}");
            }
            $definition = ProtoMessageToSwagger::enumToDefinition($message);
            $swagger->addDefinition($definition);
        }

        $this->makeSwaggerFile($swagger);
        return Command::SUCCESS;
    }

    protected function makeSwaggerFile(Swagger $swagger)
    {
        $json = json_encode($swagger->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->getHomePath("")."/swagger.json", $json);
    }
}