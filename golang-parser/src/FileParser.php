<?php


namespace GoLang\Parser;


abstract class FileParser
{
    abstract public function __construct(array $array, int &$offset, string $doc);

    /**
     * 遇到第一个结束符号返回
     *
     * @param  array  $array
     * @param  int  $offset
     * @param  string  $stopStr
     * @return array
     */
    protected function onStopWithFirstStr(array $array, int &$offset, string $stopStr): array
    {
        $got = [];
        for (; $offset < count($array); $offset++) {
            $str          = $array[$offset];
            $got[$offset] = $str;
            if ($stopStr == $str) {
                break;
            }
        }

        return $got;
    }


    /**
     * 字符串剪切
     * @param  string  $begin
     * @param  string  $end
     * @param  array  $arr
     * @return array
     */
    protected static function cutArray(string $begin, string $end, array $arr): array
    {
        $got = [];
        $on  = $count = 0;
        foreach ($arr as $item) {
            switch ($on) {
                case 0:
                    if ($item == $begin) {
                        $on = $count = 1;
                    }
                    break;
                case 1:
                    if ($item == $end) {
                        $count--;
                        if ($count <= 0) {
                            $on = 2;
                        } else {
                            $got[] = $item;
                        }
                    } else {
                        $got[] = $item;
                    }
                    break;
            }
        }
        return $got;
    }
}