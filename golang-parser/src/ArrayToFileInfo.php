<?php


namespace GoLang\Parser;


use GoLang\Parser\FileParser\Func;
use GoLang\Parser\FileParser\GoConst;
use GoLang\Parser\FileParser\GoVar;
use GoLang\Parser\FileParser\Import;
use GoLang\Parser\FileParser\Package;
use GoLang\Parser\FileParser\Type;
use ProtoParser\StringHelp;

class ArrayToFileInfo
{
    public static function toInfo(GolangToArray $goArray): array
    {
        $fileInfo = [];
        $doc      = '';
        $array    = $goArray->getArray();

        for ($offset = 0; $offset < count($array); $offset++) {
            $str = $array[$offset];
            switch ($str) {
                case "package":
                    $fileInfo["package"][] = new Package($array, $offset, $doc);
                    $doc = '';
                    break;
                case "import":
                    $fileInfo["import"][] = new Import($array, $offset, $doc);
                    $doc = '';
                    break;
                case "type":
                    $fileInfo["type"][] = new Type($array, $offset, $doc);
                    $doc = '';
                    break;
                case "func":
                    $fileInfo["func"][] = new Func($array, $offset, $doc);
                    $doc = '';
                    break;
                case "const":
                    $fileInfo["const"][] = new GoConst($array, $offset, $doc);
                    $doc = '';
                    break;
                case "var":
                    $fileInfo["var"][] = new GoVar($array, $offset, $doc);
                    $doc = '';
                    break;
                default:
                    if ( substr($str,0,2)=="//" ) {
                        $doc .= $str;
                    }
                    break;
            }
        }
        return $fileInfo;
    }
}