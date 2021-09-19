<?php


namespace GoLang\Parser\FileParser;


use GoLang\Parser\FileParser;
use GoLang\Parser\GolangToArray;
use ProtoParser\StringHelp;

class Type extends FileParser
{
    protected $name;
    protected $type;
    protected $attributes = [];
    protected $isStruct = false;
    protected $doc;

    public function __construct(array $array, int &$offset, string $doc)
    {
        $this->doc = trim($doc);

        $temp = $offset;
        $arr  = self::onStopWithFirstStr($array, $temp, PHP_EOL);

        if (strpos(implode(" ", $arr), " {")) {
            $this->parser($array, $offset);
        } else {
            $offset         = $temp;
            $this->isStruct = false;

            $arr        = array_values($arr);
            $this->type = $arr[4];
            $this->name = $arr[2];
        }
    }

    public function parser(array $array, int &$offset)
    {
        $arr = StringHelp::onStopWithSymmetricStr($array, $offset, "{", "}");
        $arr = array_values($arr);

        $this->isStruct = true;
        $this->name     = $arr[2];
        $this->type     = $arr[2];

        $code    = implode("", $arr);
        $code    = StringHelp::cutStr("{", "}", $code);
        $goArray = new GolangToArray('', trim($code));

        $tempAttr = [];
        foreach ($goArray->getArray() as $str) {
            if ($str == PHP_EOL) {
                if ($tempAttr) {
                    $this->setAttr($tempAttr);
                }

                $tempAttr = [];
            } else {
                if ($tempAttr || trim($str)) {
                    if (substr($str,0,2)!="//"){
                        $tempAttr[] = $str;
                    }
                }
            }
        }
        if ($tempAttr) {
            $this->setAttr($tempAttr);
        }
    }

    protected function setAttr(array $tempAttr)
    {
        $ok = true;
        foreach ($tempAttr as $item) {
            if ($item == "{") {
                $ok = false;
            }
        }
        if ($ok) {
            $attr = new Attribute();
            $attr->setName($tempAttr[0]);
            if (!isset($tempAttr[2])){
                // 内嵌类型
                return false;
            }
            $type = $tempAttr[2];
            if (!$type) {
                // 注释
                return false;
            }
            switch (substr($type, 0,1)) {
                case "*":
                    $attr->setIsPointer(true);
                    $attr->setType(substr($type, 1));

                    if (isset($tempAttr[4]) && substr($tempAttr[4],0, 1) == '`') {
                        $tagStr = trim($tempAttr[4],'`');
                        $tagArr = [];
                        foreach (explode(' ', $tagStr) as $tagBase) {
                            $tagTempArr             = explode(':', $tagBase);
                            $tagArr[$tagTempArr[0]] = trim($tagTempArr[1], '"');
                        }
                        $attr->setTags($tagArr);
                    }
                    break;
                case "[":
                    $attr->setNotArray(false);
                    if (substr($type, 2,1) == "*") {
                        $attr->setIsPointer(true);
                        $attr->setType(substr($type, 3));
                    }
                    break;
                default:
                    $attr->setType($type);
                    break;
            }

            $this->attributes[] = $attr;
        } else {
            // TODO 复杂结构不支持
            // 当前版本只是为了注入, 需要注入的结构不建议使用匿名结构
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return bool
     */
    public function isStruct(): bool
    {
        return $this->isStruct;
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        return $this->doc;
    }
}