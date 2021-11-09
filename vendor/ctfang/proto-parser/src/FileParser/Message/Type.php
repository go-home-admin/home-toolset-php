<?php


namespace ProtoParser\FileParser\Message;


use ProtoParser\ProtoToArray;
use ProtoParser\ProtoType;

class Type
{
    // 普通, proto自带类型
    const Base = 1;
    // 数组
    const bTypeArray = 2;
    // 任意一个
    const bTypeOnef = 4;
    // 依赖其他Message
    const bTypeObject = 8;

    /**
     * 特殊结构
     * @var int
     */
    protected $bType = 0;

    protected $type;

    protected $name;

    protected $doc;

    protected $num;

    public function __construct(array $array, &$offset, string $doc)
    {
        $this->doc = $doc;

        $this->parserArray($array, $offset);
    }

    public function parserArray(array $array, int &$offset)
    {
        $init = 0;
        for (; $offset < count($array); $offset++) {
            $str    = $array[$offset];
            $data[] = $str;

            if ($init == 0) {
                $this->type = $str;
                switch ($str) {
                    case "repeated":
                        $this->bType += self::bTypeArray;
                        $init        = -2;
                        break;
                    case "oneof":
                        $this->bType = self::bTypeOnef;
                        $init        = -2;
                        break;
                    default:
                        if ($this->bType==0 && in_array($str, ProtoType::ALl)) {
                            $this->type  = $str;
                            $this->bType = self::Base;
                        } else {
                            if ($str) {
                                $this->type  = $str;
                                $this->bType += self::bTypeObject;
                            }
                        }
                        break;
                }
            } else {
                if ($init == 2) {
                    $this->name = $str;
                } else {
                    if ($init == 5) {
                        $this->num = $str;
                    } else {
                        if ($str == ';') {
                            break;
                        }
                    }
                }
            }

            $init++;
        }
    }

    /**
     * @return mixed
     */
    public function getBType()
    {
        return $this->bType;
    }

    /**
     * @param  mixed  $bType
     */
    public function setBType($bType): void
    {
        $this->bType = $bType;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  mixed  $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  mixed  $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        return trim($this->doc, "//");
    }

    /**
     * @param  string  $doc
     */
    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }

    /**
     * @return mixed
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * @param  mixed  $num
     */
    public function setNum($num): void
    {
        $this->num = $num;
    }
}















