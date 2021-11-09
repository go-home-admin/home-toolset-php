<?php


namespace ProtoParser\FileParser\Message;


use ProtoParser\FileParser\Base;
use ProtoParser\ProtoFile;
use ProtoParser\ProtoToArray;
use ProtoParser\StringHelp;

class Message
{
    protected $values = [];
    protected $doc = '';
    protected $name = '';
    protected $option = [];

    public function __construct(array $source)
    {
        $array      = array_values($source['code']);
        $this->doc  = $source['doc'];
        $this->name = $array[2];

        $offset = 0;
        for (; $offset < count($array); $offset++) {
            if ($array[$offset] == "{") {
                break;
            }
        }

        $doc = '';
        for (; $offset < count($array); $offset++) {
            $str = $array[$offset];

            switch ($str) {
                case "option":
                    list($key, $value) = $this->parserOption($array, $offset);
                    $this->option[$key] = $value;
                    $doc                = '';
                    break;
                case "repeated":
                    // 数组
                    $this->values[] = new Type($array, $offset, $doc);
                    $doc            = '';
                    break;
                case "message":
                    $this->values[] = new Message([
                        'code' => StringHelp::onStopWithSymmetricStr($array, $offset),
                        'doc'  => $doc,
                    ]);
                    $doc            = '';
                    break;
                case "oneof":
                    $this->values[] = new Message([
                        'code' => StringHelp::onStopWithSymmetricStr($array, $offset),
                        'doc'  => $doc,
                    ]);
                    $doc            = '';
                    break;
                default:
                    // 类型
                    if (isset($str[0]) && $str[0] == '/') {
                        // 注释
                        $doc .= $str;
                    } else {
                        if (!in_array($str, ProtoToArray::Separator)) {
                            $this->values[] = new Type($array, $offset, $doc);
                            $doc            = '';
                        }
                    }
                    break;
            }
        }
    }


    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function parserOption(array $array, int &$offset): array
    {
        $key    = '';
        $inType = 0;
        for (; $offset < count($array); $offset++) {
            $str = $array[$offset];

            if ($inType == 0) {
                if ($str == '=') {
                    $inType = 1;
                } elseif (!in_array($str, ProtoToArray::Separator)) {
                    $key = $str;
                }
            } else {
                if ($inType <= 10) {
                    if ($str) {
                        switch ($str[0]) {
                            case '"':
                                $value = trim($str, '"');
                                break 2;
                            case '{':
                                $value = [];

                                $tempKey = '';
                                $last    = StringHelp::onStopWithSymmetricStr($array, $offset);
                                foreach ($last as $str) {
                                    if (!$str) {
                                        continue;
                                    }
                                    if (isset($str[0]) && $str[0] == '/') {
                                        // 注释
                                    } elseif (!in_array($str, ProtoToArray::Separator)) {
                                        if (!$tempKey) {
                                            $tempKey = $str;
                                        } else {
                                            $value[$tempKey] = trim($str, '"');
                                            $tempKey         = '';
                                        }
                                    }
                                }
                                break 2;
                            default:

                                break;
                        }
                    }
                }
            }
        }

        return [$key, $value ?? []];
    }

    /**
     * @return Type[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param  array  $values
     */
    public function setValues(array $values): void
    {
        $this->values = $values;
    }

    /**
     * @return array
     */
    public function getOption(): array
    {
        return $this->option;
    }

    /**
     * @param  array  $option
     */
    public function setOption(array $option): void
    {
        $this->option = $option;
    }

    /**
     * @return mixed|string
     */
    public function getDoc()
    {
        return $this->doc;
    }

    /**
     * @param  mixed|string  $doc
     * @return Message
     */
    public function setDoc($doc)
    {
        $this->doc = $doc;
        return $this;
    }
}