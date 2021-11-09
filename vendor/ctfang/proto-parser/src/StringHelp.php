<?php


namespace ProtoParser;


class StringHelp
{
    /**
     * 驼峰命名转下划线命名
     * @param $str
     * @return string
     */
    public static function toUnderScore($str): string
    {
        $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
            return '_'.strtolower($matchs[0]);
        }, $str);
        return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
    }

    /**
     * 下划线命名到驼峰命名
     * @param $str
     * @return mixed|string
     */
    public static function toCamelCase($str)
    {
        $array  = explode('_', $str);
        $result = $array[0];
        $len    = count($array);
        if ($len > 1) {
            for ($i = 1; $i < $len; $i++) {
                $result .= ucfirst($array[$i]);
            }
        }
        return $result;
    }

    /**
     * 字符串剪切
     * @param $begin
     * @param $end
     * @param $str
     * @return string
     */
    public static function cutStr($begin, $end, $str): string
    {
        if ($begin !== '') {
            $b = mb_strpos($str, $begin) + mb_strlen($begin);
        } else {
            $b = 0;
        }

        $e = mb_strpos($str, $end) - $b;
        return mb_substr($str, $b, $e);
    }

    /**
     * 单字符，按块切割
     * @param  string  $begin
     * @param  string  $end
     * @param  string  $str
     * @return string
     */
    public static function cutChar(string $begin, string $end, string $str): string
    {
        if (strlen($begin) != 1 && strlen($end) != 1) {
            die("使用错误, 单字符，按块切割");
        }

        $firstHas = false;
        $count    = 0;
        $start    = 0;

        $stop = $start;
        for ($i = 0; $i < strlen($str); $i++) {
            $temp = $str[$i];
            if ($temp == $begin && !$firstHas) {
                $count++;
                $start    = $i;
                $firstHas = true;
            } elseif ($temp == $end) {
                $count--;
            }
            if ($firstHas && $count <= 0) {
                $stop = $i;
                break;
            }
        }

        return mb_substr($str, $start, $stop - $start + 1);
    }

    /**
     * 坚持最后一个字符串
     * @param  string  $str
     * @param  string  $check
     * @return bool
     */
    public static function checkLast(string $str, string $check): bool
    {
        $count = strlen($str);
        $last  = $str[$count-1] ?? '';
        if ($last === $check) {
            return true;
        }
        return false;
    }


    /**
     * 截取对称符号 {} 里的内容
     *
     * @param  array  $array
     * @param  int  $offset
     * @param  string  $startStr
     * @param  string  $stopStr
     * @return array
     */
    public static function onStopWithSymmetricStr(
        array $array,
        int &$offset,
        string $startStr = "{",
        string $stopStr = "}"
    ): array {
        $init  = false;
        $count = 0;
        $got   = [];
        for (; $offset < count($array); $offset++) {
            $str          = $array[$offset];
            $got[$offset] = $str;
            if ($startStr == $str) {
                $init = true;
                $count++;
            } elseif ($stopStr == $str) {
                $count--;
            }
            if ($init && $count <= 0) {
                break;
            }
        }

        return $got;
    }


    /**
     * 遇到第一个结束符号返回
     *
     * @param  array  $array
     * @param  int  $offset
     * @param  string  $stopStr
     * @return array
     */
    public function onStopWithFirstStr(array $array, int &$offset, string $stopStr): array
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
}