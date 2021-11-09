<?php


namespace ProtoParser\Swagger;


use ProtoParser\FileParser\Message\Enum;
use ProtoParser\FileParser\Message\Message;
use ProtoParser\FileParser\Message\Type;
use ProtoParser\ProtoType;

class ProtoMessageToSwagger
{
    public static function toResponse(Message $message): Response
    {
        $response = new Response();
        $response->setSchema([
            "\$ref" => "#/definitions/{$message->getName()}"
        ]);
        $response->setDescription($message->getDoc());
        return $response;
    }

    /**
     * @param  \ProtoParser\FileParser\Message\Enum  $message
     * @return \ProtoParser\Swagger\Definition
     */
    public static function enumToDefinition(Enum $message): Definition
    {
        $definition = new Definition();
        $definition->setName($message->getName());

        $pars = [];
        foreach ($message->getValues() as $value => $arr) {
            $doc          = str_replace(['//', '"', ' '], '', $arr["doc"]);
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
        $definition->setDescription("枚举对象:" . $message->getDoc());
        return $definition;
    }

    public static function toDefinition(Message $message, Swagger $swagger = null): Definition
    {
        $definition = new Definition();
        $definition->setName($message->getName());
        $pars = [];
        foreach ($message->getValues() as $type) {
            if ($type instanceof Message) {
                foreach ($type->getValues() as $type2) {
                    $mType = $type2->getBType();
                    if ($mType == $type2::Base) {
                        $pars2[$type2->getName()] = [
                            "type"        => self::toSwaggerType($type2),
                            "format"      => $type2->getType(),
                            "description" => $type2->getDoc(),
                        ];
                    } elseif ($mType & $type2::bTypeArray) {
                        // 数组
                        $lineType = $type2->getType();
                        if ("google.protobuf.Any" == $lineType) {
                            $lineType = "Null";
                        }else{
                            $lineType = self::toSwaggerType($type2);
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
                        "type"        => self::toSwaggerType($type),
                        "format"      => $type->getType(),
                        "description" => $type->getDoc(),
                    ];
                } elseif ($mType & $type::bTypeArray) {
                    // 数组
                    $lineType = $type->getType();
                    if ("google.protobuf.Any" == $lineType) {
                        $lineType = "Null";
                    }

                    if (in_array($lineType, ProtoType::ALl)) {
                        $lineType = self::toSwaggerType($type);
                        $pars[$type->getName()] = [
                            "type"        => "array",
                            "description" => $type->getDoc(),
                            "items"       => [
                                "type"=> $lineType,
                            ],
                        ];
                    }else{
                        $pars[$type->getName()] = [
                            "type"        => "array",
                            "description" => $type->getDoc(),
                            "items"       => [
                                "\$ref" => "#/definitions/{$lineType}"
                            ],
                        ];
                    }
                } elseif ($mType & $type::bTypeObject) {
                    // 其他对象引用
                    $lineType = $type->getType();
                    if ("google.protobuf.Any" == $lineType) {
                        $lineType = "Null";
                    }
                    $pars[$type->getName()] = [
                        "\$ref" => "#/definitions/{$lineType}"
                    ];
                }
            }
        }
        $definition->setDescription($message->getDoc());
        $definition->setProperties($pars);
        return $definition;
    }

    public static function toParameter(Message $message, string $method = 'get')
    {
        if ($method == 'get') {
            $got = [];
            // 写到url上
            foreach ($message->getValues() as $type) {
                $parameter = new Parameter();
                // 其他对象引用
                $parameter->setName($message->getName());
                $parameter->setDescription($message->getDoc());
                if ($type->getBType() == $type::Base) {
                    // proto自带类型
                    $parameter->setName($type->getName());
                    $parameter->setDescription($type->getDoc());
                    $parameter->setFormat($type->getType());
                    $parameter->setType(self::toSwaggerType($type));
                } elseif ($type->getBType() & $type::bTypeObject) {
                    // 其他对象引用
                    $lineType = $type->getType();
                    if ("google.protobuf.Any" == $lineType) {
                        $lineType = "Null";
                    }
                    $parameter->setName($type->getName());
                    $parameter->setDescription($type->getDoc());

                    if (in_array($lineType, ProtoType::ALl)) {
                        $lineType = self::toSwaggerType($type);
                        $parameter->setType("array");
                        $parameter->setItems([
                            "type"=> $lineType,
                        ]);
                    }else{
                        $parameter->setSchema([
                            "\$ref" => "#/definitions/{$lineType}"
                        ]);
                    }
                }
                $parameter->setIn("query");
                $got[] = $parameter;
            }
            return $got;
        } elseif ($method == 'post') {
            $parameter = new Parameter();
            // 其他对象引用
            $parameter->setName($message->getName());
            $parameter->setDescription($message->getDoc());
            $parameter->setSchema([
                "\$ref" => "#/definitions/{$message->getName()}"
            ]);
            $parameter->setIn("body");
            return [$parameter];
        }
        return [];
    }

    // proto自带类型 转 swagger
    public static function toSwaggerType(Type $type): string
    {
        $source = $type->getType();
        if ($type->getBType() == $type::bTypeArray) {
            return 'array';
        }
        if (in_array($source, ['string', 'bytes', 'double'])) {
            $typeIn = 'string';
        } elseif (in_array($source, ['bool'])) {
            $typeIn = 'boolean';
        } elseif (in_array($source, [
            "float",
            "int32",
            "sint32",
            "uint32",
            "int64",
            "sint64",
            "uint64",
            "fixed32",
            "fixed64",
            "sfixed32",
            "sfixed64",
        ])) {
            $typeIn = 'integer';
        } elseif ("google.protobuf.Any" == $source){
            $typeIn = 'Null';
        } else {
            $typeIn = $source;
        }
        return $typeIn;
    }
}