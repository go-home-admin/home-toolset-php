<?php


namespace ProtoParser\FileParser;


use ProtoParser\FileParser\Service\Service;

class ServiceFileParser extends Base
{
    protected $value;

    public function parser(array $arr)
    {
        $than = $this;
        foreach ($arr as $arr2) {
            $message = new Service($arr2);

            $than->value[$message->getName()] = $message;
        }
        return $than;
    }

    /**
     * @return Service[]
     */
    public function getArray(): array
    {
        return $this->value;
    }
}