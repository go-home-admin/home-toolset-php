<?php

use ProtoParser\ProtoParser;
use ProtoParser\ProtoToArray;
use ProtoParser\Swagger\Path;
use ProtoParser\Swagger\ProtoMessageToSwagger;
use ProtoParser\Swagger\Swagger;
use ProtoParser\Swagger\Tag;

require_once './../vendor/autoload.php';

$swagger = new Swagger();

$file = './../test/proto/controllers/home.proto';
$file = realpath($file);

$content = file_get_contents($file);

$parserToArr = new ProtoToArray($content);
$parser      = new ProtoParser();
$proto       = $parser->parser($parserToArr);


$services = $proto->getService();
// 按路由组划分
$cacheTag = [];
foreach ($services->getArray() as $serviceName => $service) {
    $options = $service->getOptions();
    foreach ($options as $optionKey => $optionValue) {
        if ("http.Route" == $optionKey) {
            $tag = new Tag();
            $tag->setName($optionValue->getValue());
            $tag->setDescription($optionValue->getDoc());
            $swagger->addTag($tag);

            $cacheTag[$tag->getName()] = $optionValue->getValue();
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
                $path   = new Path();
                $path->setSummary(explode("//", $rpc->getDoc())[0]);
                $path->setMethod($method);
                $path->setKey($optionValue);
                $path->setDescription(str_replace("//","\n\n",$rpc->getDoc()));
                $path->setTags($cacheTag);

                $parameters = [];
                $message    = $rpc->getParameter();
                $message    = $parser->getMessageWithAll($message);
                if ($message) {
                    $parameters = ProtoMessageToSwagger::toParameter($message,$method);
                }
                $path->setParameters($parameters);

                $responses = [];
                $message   = $rpc->getResponse();
                $message   = $parser->getMessageWithAll($message);
                if ($message) {
                    $responses = ProtoMessageToSwagger::toResponse($message);
                }
                $path->setResponses(['200' => $responses]);

                $swagger->addPath($path);
            }
        }
    }
}

// 所有message
foreach ($proto->getMessageWithAll() as $name => $message) {
    $definition = ProtoMessageToSwagger::toDefinition($message);
    $swagger->addDefinition($definition);
}
// 所有enum
foreach ($proto->getEnumWithAll() as $name => $message) {
    $definition = ProtoMessageToSwagger::toDefinition($message);
    $swagger->addDefinition($definition);
}


$json = json_encode($swagger->toArray(), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
file_put_contents(__DIR__."/web/swagger.json", $json);