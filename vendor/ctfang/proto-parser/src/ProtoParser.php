<?php


namespace ProtoParser;


use ProtoParser\FileParser\Base;
use ProtoParser\FileParser\EnumFileParser;
use ProtoParser\FileParser\ImportFileParser;
use ProtoParser\FileParser\Message\Message;
use ProtoParser\FileParser\MessageFileParser;
use ProtoParser\FileParser\OptionFileParser;
use ProtoParser\FileParser\PackageFileParser;
use ProtoParser\FileParser\ServiceFileParser;
use ProtoParser\FileParser\SyntaxFileParser;

class ProtoParser
{
    private static $parserObject = [];

    public $file;
    protected $syntax;
    protected $package;
    protected $import;
    protected $option;
    protected $service;
    /**
     * @var MessageFileParser
     */
    protected $message;
    /**
     * @var EnumFileParser
     */
    protected $enum = [];

    public function __construct()
    {
        self::$parserObject = [
            "syntax"  => new SyntaxFileParser(),
            "package" => new PackageFileParser(),
            "import"  => new ImportFileParser(),
            "option"  => new OptionFileParser(),
            "service" => new ServiceFileParser(),
            "message" => new MessageFileParser(),
            "enum"    => new EnumFileParser(),
        ];
    }

    /**
     * @var ProtoParser[]
     */
    protected static $allProto = [];

    public function parser(ProtoToArray $protoToArray, string $file = ''): self
    {
        $self     = clone $this;
        $fileInfo = $this->parserArrayToFileInfo($protoToArray);

        foreach ($fileInfo as $name => $item) {
            $parser = clone self::$parserObject[$name];

            if ($parser instanceof Base) {
                $self->{$name} = $parser->parser($item);
            }
        }
        $self->file = $file;
        self::$allProto[$file] = $self;
        return $self;
    }

    /**
     * @param  ProtoToArray  $protoToArray
     * @return array
     */
    public function parserArrayToFileInfo(ProtoToArray $protoToArray): array
    {
        $fileInfo = [];
        $doc      = '';
        $array    = $protoToArray->getArray();
        for ($offset = 0; $offset < count($array); $offset++) {
            $str = $array[$offset];
            switch ($str) {
                case "syntax":
                case "package":
                    $fileInfo[$str] = $this->onStopWithFirstStr($array, $offset, ";");
                    $doc            = '';
                    break;
                case "import":
                case "option":
                    $fileInfo[$str][] = $this->onStopWithFirstStr($array, $offset, ";");
                    $doc              = '';
                    break;
                case "service":
                case "message":
                case "enum":
                    $fileInfo[$str][] = [
                        'doc'  => $doc,
                        'code' => $this->onStopWithSymmetricStr($array, $offset),
                    ];
                    $doc              = '';
                    break;
                default:
                    if (!in_array($str, ProtoToArray::Separator)) {
                        $doc = $str;
                    }
                    break;
            }
        }
        return $fileInfo;
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

    /**
     * 截取对称符号 {} 里的内容
     *
     * @param  array  $array
     * @param  int  $offset
     * @param  string  $startStr
     * @param  string  $stopStr
     * @return array
     */
    public function onStopWithSymmetricStr(
        array $array,
        int &$offset,
        string $startStr = "{",
        string $stopStr = "}"
    ): array {
        return StringHelp::onStopWithSymmetricStr($array, $offset, $startStr, $stopStr);
    }

    /**
     * @return array
     */
    public static function getParserObject(): array
    {
        return self::$parserObject;
    }

    /**
     * @param  array  $parserObject
     */
    public static function setParserObject(array $parserObject): void
    {
        self::$parserObject = $parserObject;
    }

    /**
     * @return mixed
     */
    public function getSyntax()
    {
        return $this->syntax;
    }

    /**
     * @param  mixed  $syntax
     */
    public function setSyntax($syntax): void
    {
        $this->syntax = $syntax;
    }

    /**
     * @return PackageFileParser
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * @param  mixed  $package
     */
    public function setPackage($package): void
    {
        $this->package = $package;
    }

    /**
     * @return mixed
     */
    public function getImport()
    {
        return $this->import;
    }

    /**
     * @param  mixed  $import
     */
    public function setImport($import): void
    {
        $this->import = $import;
    }

    /**
     * @return mixed
     */
    public function getOption()
    {
        return $this->option;
    }

    /**
     * @param  mixed  $option
     */
    public function setOption($option): void
    {
        $this->option = $option;
    }

    /**
     * @return ServiceFileParser
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param  mixed  $service
     */
    public function setService($service): void
    {
        $this->service = $service;
    }

    /**
     * @return MessageFileParser|Message
     */
    public function getMessage(string $name = '')
    {
        if (!$name) {
            return $this->message;
        } else if ($this->message) {
            $all = $this->message->get();
            if (!$all) {
                return [];
            }
            return $all[$name] ?? null;
        }
        return [];
    }

    /**
     * @param  mixed  $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return EnumFileParser[]
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @param  mixed  $enum
     */
    public function setEnum($enum): void
    {
        $this->enum = $enum;
    }

    /**
     * 在所有已经解析过的文件里查询指定message
     * @param  string  $name
     * @return MessageFileParser|Message|null
     */
    public function getMessageWithAll(string $name = '')
    {
        if ($name) {
            foreach (self::$allProto as $proto) {
                $message = $proto->getMessage($name);

                if ($message) {
                    return $message;
                }
            }
            return null;
        } else {
            $got = [];
            foreach (self::$allProto as $proto) {
                $messages = $proto->getMessage($name);

                if ($messages){
                    foreach ($messages->getValues() as $message) {
                        $got[] = [$proto->getPackage()->getValue(),$message];
                    }
                }
            }
            return $got;
        }
    }


    public function getEnumWithAll()
    {
        $got = [];
        foreach (self::$allProto as $proto) {
            $messages = $proto->getEnum();

            if ($messages){
                foreach ($messages->getValues() as $message) {
                    $got[] = [$proto->getPackage()->getValue(),$message];
                }
            }
        }
        return $got;
    }

    public function getAllProto(): array
    {
        return self::$allProto;
    }
}