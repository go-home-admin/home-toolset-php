<?php


namespace ProtoParser\FileParser;


class SyntaxFileParser extends Base
{
    protected $value;

    public function parser(array $arr)
    {
        $arr  = array_values($arr);
        $than = $this;

        $than->value = trim($arr[2], '"');
        return $than;
    }
}