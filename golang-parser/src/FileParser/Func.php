<?php


namespace GoLang\Parser\FileParser;


use GoLang\Parser\FileParser;
use ProtoParser\StringHelp;

class Func extends FileParser
{
    protected $name;
    protected $struct;
    protected $parameter = [];
    protected $returns = [];
    protected $isStruct = false;
    protected $doc;

    /**
     * TODO
     * @param  array  $array
     * @param  int  $offset
     * @param  string  $doc
     */
    public function __construct(array $array, int &$offset, string $doc)
    {
        $this->doc = trim($doc);

        $arr = StringHelp::onStopWithSymmetricStr($array, $offset);
        $arr = array_values($arr);

        if ($arr[2] == "(") {
            // 属于方法集
            $this->setIsStruct(true);
        } else {
            // 普通函数
            $this->setName($arr[2]);
            $this->setIsStruct(false);

            $offsetPar = 0;
            $arrPar    = $this->cutArray('(', ')', $arr);
            $arrPar    = array_values($arrPar);
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
     * @param  mixed  $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getStruct()
    {
        return $this->struct;
    }

    /**
     * @param  mixed  $struct
     */
    public function setStruct($struct): void
    {
        $this->struct = $struct;
    }

    /**
     * @return bool
     */
    public function isStruct(): bool
    {
        return $this->isStruct;
    }

    /**
     * @param  bool  $isStruct
     */
    public function setIsStruct(bool $isStruct): void
    {
        $this->isStruct = $isStruct;
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        return $this->doc;
    }

    /**
     * @param  string  $doc
     */
    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }
}