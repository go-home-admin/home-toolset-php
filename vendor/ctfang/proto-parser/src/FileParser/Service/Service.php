<?php


namespace ProtoParser\FileParser\Service;


use ProtoParser\FileParser\Base;
use ProtoParser\ProtoFile;
use ProtoParser\ProtoToArray;
use ProtoParser\StringHelp;

class Service
{
    protected $name;
    protected $doc;
    protected $option = [];
    protected $rpc = [];

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
                    $option = new Option();
                    $option->setDoc($doc);
                    $option->setKey($key);
                    $option->setValue($value);
                    $this->option[$key] = $option;
                    $doc                = '';
                    break;
                case "rpc":
                    list($key, $value) = $this->parserRpc($array, $offset);
                    $rpc = new Rpc();
                    $rpc->setDoc($doc);
                    $rpc->setName($value["name"]);
                    $rpc->setParameter($value["input"]);
                    $rpc->setResponse($value["return"]);
                    $rpc->setOptions($value["option"]);
                    $this->rpc[$rpc->getName()] = $rpc;
                    $doc             = '';
                    break;
                default:
                    // 不识别是注释
                    if (strpos($str, '//') === 0) {
                        $doc .= $str;
                    }
                    break;
            }
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function parserRpc(array $source, int &$offset): array
    {
        $array = StringHelp::onStopWithSymmetricStr($source, $offset);
        $array = array_values($array);

        $on = 'name';
        for ($offset2 = 1; $offset2 < count($array); $offset2++) {
            $str = $array[$offset2];
            if (!in_array($str, ["", " ", "(", ")", "returns"])) {
                if ($on == 'name') {
                    $value['name'] = $str;
                    $on = 'input';
                } elseif ($on == 'input') {
                    $value['input'] = $str;
                    $on = 'return';
                } elseif ($on == 'return') {
                    $value['return'] = $str;
                    break;
                }
            }
        }
        $key   = '';
        for ($offset2; $offset2 < count($array); $offset2++) {
            $str = $array[$offset2];
            if ($str == 'option') {
                list($key, $value2) = $this->parserOption($array, $offset2);
                $value["option"][$key] = $value2;
            }
        }

        return [$key, $value ?? []];
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
     * @return mixed
     */
    public function getDoc(): mixed
    {
        return $this->doc;
    }

    /**
     * @param  mixed  $doc
     */
    public function setDoc(mixed $doc): void
    {
        $this->doc = $doc;
    }

    /**
     * @return Option[]
     */
    public function getOptions(): array
    {
        return $this->option;
    }

    /**
     * @param  array  $option
     */
    public function setOptions(array $option): void
    {
        $this->option = $option;
    }

    /**
     * @return Rpc[]
     */
    public function getRpc(): array
    {
        return $this->rpc;
    }

    /**
     * @param  array  $rpc
     */
    public function setRpc(array $rpc): void
    {
        $this->rpc = $rpc;
    }
}