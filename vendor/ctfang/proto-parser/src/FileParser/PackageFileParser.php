<?php


namespace ProtoParser\FileParser;


class PackageFileParser extends Base
{
    protected $value;

    public function parser(array $arr)
    {
        $arr = array_values($arr);
        $than = $this;

        $than->value = trim($arr[2], '"');
        return $than;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}