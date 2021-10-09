<?php


namespace GoLang\Parser;

/**
 * 分词, 去除无效字符(多个空格等)
 * @package GoLang\Parser
 */
class GolangToArray
{
    const onNew = 'onNew';
    const onDoc = 'onDoc';
    const onStr = 'onStr';

    const Separator = [
        "\n", "\t", ";", ",", "(", ")", "{", "}", " ", "=",
    ];

    private $array = [];
    private $offsetToArray = [];
    private $sourceArray = [];

    public $file;

    public function __construct(string $file, string $content = '')
    {
        if ($file) {
            $this->file = realpath($file);
            $content    = file_get_contents($this->file);
        }

        $word = '';

        $onState           = self::onNew;
        $this->sourceArray = $this->mbSplit($content);

        $onStrStateCache = '';
        $onStrStateCount = 0;

        for ($offset = 0; $offset < count($this->sourceArray); $offset++) {
            $char = $this->sourceArray[$offset];
            switch ($onState) {
                case self::onNew:
                    // 全新解析
                    if ($this->checkDoc($this->sourceArray, $offset)) {
                        // 进入注释
                        $word    .= $char;
                        $onState = self::onDoc;
                    } elseif ($this->checkStr($this->sourceArray, $offset, $onStrStateCache, $onStrStateCount)) {
                        // 进入字符串解析
                        $word    .= $char;
                        $onState = self::onStr;
                    } elseif (in_array($char, self::Separator)) {
                        // 遇到分隔符号
                        if ($word != "") {
                            $this->addArray(trim($word), $offset);
                            $this->addArray($char, $offset);
                            $word = '';
                        } else {
                            if ($char != " ") {
                                $this->addArray($char, $offset);
                                $word = '';
                            }
                        }
                        $onState = self::onNew;
                    } else {
                        $word .= $char;
                    }
                    break;
                case self::onDoc:
                    // 解析注释当中
                    if ($char == PHP_EOL) {
                        $this->addArray(trim($word), $offset);
                        $this->addArray($char, $offset);
                        $word    = '';
                        $onState = self::onNew;
                    } else {
                        $word .= $char;
                    }
                    break;
                case self::onStr:
                    if ($this->checkStr($this->sourceArray, $offset, $onStrStateCache, $onStrStateCount)) {
                        $this->addArray(trim($word.$char), $offset);
                        $word    = '';
                        $onState = self::onNew;
                    } else {
                        $word .= $char;
                    }
                    break;
                default:
                    break;
            }
        }

        if ($onState == self::onNew) {
            if ($word != "") {
                $this->addArray(trim($word), $offset);
            }
        }
    }

    public function checkDoc(array $allStrArr, int $offset): bool
    {
        if ($allStrArr[$offset] == '/' && (isset($allStrArr[$offset + 1]) && $allStrArr[$offset + 1] == '/')) {
            return true;
        }

        return false;
    }

    public function checkStr(array $allStrArr, int $offset, &$onStrStateCache, &$onStrStateCount): bool
    {
        $ok = false;
        switch ($allStrArr[$offset]) {
            case '"':
                break;
            case '`':
                $ok = true;
                break;
        }

        if ($ok) {
            if ($onStrStateCount == 0) {
                $onStrStateCount++;
                $onStrStateCache = $allStrArr[$offset];
                return true;
            }
            if ($onStrStateCache == $allStrArr[$offset]) {
                $onStrStateCount--;
                if ($onStrStateCount <= 0) {
                    $onStrStateCache = '';
                    return true;
                }
            }
        }

        return false;
    }

    protected function mbSplit($string, $len = 1): ?array
    {
        $array  = [];
        $start  = 0;
        $strLen = mb_strlen($string);
        while ($strLen) {
            $array[] = mb_substr($string, $start, $len, "utf8");
            $string  = mb_substr($string, $len, $strLen, "utf8");
            $strLen  = mb_strlen($string);
        }
        return $array;
    }

    public function addArray(string $char, int $offset)
    {
        $this->array[]                            = $char;
        $this->offsetToArray[count($this->array)] = $offset;
    }

    public function getFileOffset(int $index): int
    {
        return $this->offsetToArray[$index];
    }

    public function getFileString(int $start, int $end): string
    {
        return implode("", array_slice($this->sourceArray, $start, $end - $start));
    }

    public function getArray(): array
    {
        return $this->array;
    }


    /**
     * 单字符，按块切割
     * @param  string  $begin
     * @param  string  $end
     * @param  string  $str
     * @return string
     */
    public static function cutChar(string $str, string $begin = '"', string $end = '"'): string
    {
        $firstHas = false;
        $count    = 0;
        $start    = 0;

        $stop = $start;
        for ($i = 0; $i < strlen($str); $i++) {
            $temp = $str[$i];
            switch ($temp) {
                case $begin:
                    if (!$firstHas) {
                        $start    = $i;
                        $firstHas = true;
                        $count++;
                    } elseif ($end == $temp) {
                        $count--;
                    }
                    break;
                case $end:
                    $count--;
                    break;
            }
            if ($firstHas && $count <= 0) {
                $stop = $i;
                break;
            }
        }

        return mb_substr($str, $start, $stop - $start + 1);
    }
}