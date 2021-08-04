<?php


namespace GoLang\Parser;


class GolangToArray
{
    const onNew = 'onNew';
    const onDoc = 'onDoc';
    const onStr = 'onStr';

    const Separator = [
        "\n", "\t", ";", ",", "(", ")", "{", "}", " ", "=",
    ];

    private $array = [];

    public $file;

    public function __construct(string $file)
    {
        $this->file = realpath($file);
        $content    = file_get_contents($this->file);

        $word = '';

        $onState   = self::onNew;
        $allStrArr = $this->mbSplit($content);

        for ($offset = 0; $offset < count($allStrArr); $offset++) {
            $char = $allStrArr[$offset];
            switch ($onState) {
                case self::onNew:
                    // 全新解析
                    if ($this->checkDoc($allStrArr, $offset)) {
                        // 进入注释
                        $word    .= $char;
                        $onState = self::onDoc;
                    } elseif ($this->checkStr($allStrArr, $offset)) {
                        // 进入字符串解析
                        $word    .= $char;
                        $onState = self::onStr;
                    } elseif (in_array($char, self::Separator)) {
                        // 遇到分隔符号
                        if ($word != "") {
                            $this->array[] = trim($word);
                            $this->array[] = $char;
                            $word          = '';
                        } else {
                            if ($char != " ") {
                                $this->array[] = $char;
                                $word          = '';
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
                        $this->array[] = trim($word);
                        $this->array[] = $char;
                        $word          = '';
                        $onState       = self::onNew;
                    } else {
                        $word .= $char;
                    }
                    break;
                case self::onStr:
                    if ($char == '"' && $allStrArr[$offset - 1] != '\\') {
                        $this->array[] = trim($word.$char);
                        $word          = '';
                        $onState       = self::onNew;
                    } else {
                        $word .= $char;
                    }
                    break;
                default:
                    break;
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

    public function checkStr(array $allStrArr, int $offset): bool
    {
        if ($allStrArr[$offset] == '"') {
            return true;
        }

        return false;
    }

    protected function mbSplit($string): ?array
    {
        // /u表示把字符串当作utf-8处理，并把字符串开始和结束之前所有的字符串分割成数组
        return preg_split('/(?<!^)(?!$)/u', $string);
    }

    public function getArray(): array
    {
        return $this->array;
    }
}