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

            $arrPar    = $this->cutArray('(', ')', $arr);
            $arrPar    = array_values($arrPar);

            // name name_got type
            $on = 'name';
            foreach ($arrPar as $str) {
                $str = trim($str);
                if ($on == 'name' && !empty($str)) {
                    $param = ['name' => $str];
                    $on    = 'name_got';
                } elseif ($on == 'name_got' && !empty($str)) {
                    if ($str == 'func') {
                        $param['name'] = 'nil';
                        $on                    = 'to)';
                    } else {
                        $param['alias']   = '';
                        $param['pointer'] = false;
                        if (strpos($str, '*')==0) {
                            $param['type']['pointer'] = true;
                            $str                      = substr($str, 1);
                        }
                        $arrParTemp            = explode('.', $str);
                        $param['type'] = end($arrParTemp);
                        if (count($arrParTemp) == 2) {
                            $param['alias'] = reset($arrParTemp);
                        }
                        $on = 'type';
                    }
                    $this->parameter[] = $param;
                } elseif ($on == 'type') {
                    if ($str == ',') {
                        $on = 'name';
                    }
                } elseif ($on == 'to)') {
                    if ($str == ')') {
                        $on = 'type';
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getParameter(): array
    {
        return $this->parameter;
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