<?php


namespace GoLang\Parser\FileParser;


use GoLang\Parser\FileParser;
use GoLang\Parser\GolangToArray;

class GoConst extends FileParser
{
    protected $value;
    protected $doc;

    public function __construct(array $array, int &$offset, string $doc, GolangToArray $goArray)
    {
        $this->doc = $doc;

        $arr = $this->onStopWithFirstStr($array, $offset, PHP_EOL);
        $arr = array_values($arr);
        if (isset($arr[2])) {
            $this->value = $arr[2];
        }
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}