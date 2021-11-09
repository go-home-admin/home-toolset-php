<?php


namespace ProtoParser\FileParser;


use ProtoParser\FileParser\Message\Enum;
use ProtoParser\FileParser\Message\Message;

class EnumFileParser extends Base
{
    protected $value;

    public function parser(array $arr)
    {
        $than = $this;
        foreach ($arr as $arr2) {
            $message = new Enum($arr2);

            $than->value[$message->getName()] = $message;
        }
        return $than;
    }

    public function get(): array
    {
        return $this->value;
    }

    public function getValues(): array
    {
        return $this->value;
    }
}