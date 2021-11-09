<?php


namespace ProtoParser;

/**
 * 对文件内容进行分词
 * @package ProtoParser
 */
class ProtoToArray
{
    const onNew = 'onNew';
    const onDoc = 'onDoc';
    const onStr = 'onStr';

    const Separator = [
        "\n", "\t", ";", ",", "(", ")", "{", "}", " ", "=", ":"
    ];

    private $array = [];

    public function __construct(string $content)
    {
        $word = '';

        $onState = self::onNew;

        $allStrArr = $this->mbSplit($content);

        for ($offset = 0; $offset < count($allStrArr); $offset++) {
            $char = $allStrArr[$offset];
            switch ($onState) {
                case self::onNew:
                    // 全新解析
                    if ($char == '/' && (isset($allStrArr[$offset + 1]) && $allStrArr[$offset + 1] == '/')) {
                        // 进入注释
                        $word    .= $char;
                        $onState = self::onDoc;
                    } elseif ($char == '"') {
                        $word    .= $char;
                        $onState = self::onStr;
                    } elseif (in_array($char, self::Separator)) {
                        // 遇到分隔符号
                        if ($word!="") {
                            $this->array[] = trim($word);
                            $this->array[] = $char;
                            $word          = '';
                        } else if ($char != " "){
                            $this->array[] = $char;
                            $word          = '';
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
                    // 解析注释当中
                    if ($char == '"') {
                        $this->array[] = trim($word);
                        $this->array[] = $char;
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