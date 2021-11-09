<?php


namespace ProtoParser\FileParser;


abstract class Base
{
    /**
     * @param  array  $arr
     * @return $this
     */
    abstract public function parser(array $arr);
}