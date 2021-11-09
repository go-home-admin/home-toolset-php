<?php


namespace ProtoParser\FileParser;


class OptionFileParser extends Base
{
    protected $value;

    public function parser(array $arr)
    {
        $than = $this;
        foreach ($arr as $arr2){
            $arr2 = array_values($arr2);

            $than->value[trim($arr2[2])] = trim($arr2[5], '"');
        }
        return $than;
    }

    public function get():array
    {
        return $this->value;
    }
}