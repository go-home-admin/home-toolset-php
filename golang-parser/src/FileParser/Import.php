<?php


namespace GoLang\Parser\FileParser;


use GoLang\Parser\FileParser;
use ProtoParser\StringHelp;

class Import extends FileParser
{
    protected $value = [];

    public function __construct(array $array, int &$offset, string $doc)
    {
        $temp = $offset;
        $arr  = self::onStopWithFirstStr($array, $temp, PHP_EOL);

        if (strpos(implode(" ", $arr), " (")) {
            $this->parser($array, $offset);
        } else {
            $offset = $temp;
            $code   = implode("", $arr);
            $code   = trim($code);
            $code   = trim($code, "import");
            $code   = trim($code);
            $this->parserCode($code);
        }
    }

    public function parser(array $array, int &$offset)
    {
        $arr  = StringHelp::onStopWithSymmetricStr($array, $offset, "(", ")");
        $code = implode("", $arr);
        $code = StringHelp::cutStr("(", ")", $code);
        $arr  = explode(PHP_EOL, $code);

        foreach ($arr as $str) {
            $str = trim($str);
            if (!$str) {
                continue;
            }

            $this->parserCode($str);
        }
    }

    protected function parserCode(string $str)
    {
        $arr = explode(" ", $str);
        if (count($arr) == 1) {
            $str = trim($str, '"');
            $arr = explode("/", $str);

            $this->value[end($arr)] = trim($str, '"');
        } else {
            $this->value[$arr[0]] = trim($arr[1], '"');
        }
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @param  array  $value
     */
    public function setValue(array $value): void
    {
        $this->value = $value;
    }
}