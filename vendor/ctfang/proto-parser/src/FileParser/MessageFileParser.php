<?php


namespace ProtoParser\FileParser;


use ProtoParser\FileParser\Message\Message;
use ProtoParser\FileParser\Message\Type;

class MessageFileParser extends Base
{
    protected $value = [];

    public function parser(array $arr)
    {
        $than = $this;
        foreach ($arr as $arr2) {
            $message = new Message($arr2);

            $than->value[$message->getName()] = $message;
        }
        return $than;
    }

    /**
     * @return Type[]
     */
    public function get(): array
    {
        return $this->value;
    }

    /**
     * @return Type[]
     */
    public function getValues(): array
    {
        return $this->value;
    }
}