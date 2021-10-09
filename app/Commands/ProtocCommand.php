<?php


namespace App\Commands;


use App\Go;
use GoLang\Parser\GolangParser;
use GoLang\Parser\GolangToArray;
use ProtoParser\DirsHelp;
use ProtoParser\StringHelp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProtocCommand extends Command
{
    // 检查go文件, 符合json扩展标签
    protected $tagStr = '@Tag';

    /**
     * @return \App\Commands\ProtocCommand
     */
    protected function configure()
    {
        return $this->setName("protoc")
            ->setDescription("拼接protoc命令所需参数")
            ->addOption(
                "protobuf",
                null,
                InputOption::VALUE_OPTIONAL,
                "框架存放proto文件的目录, 默认 = ./protobuf"
            )
            ->addOption(
                "go_out",
                null,
                InputOption::VALUE_OPTIONAL,
                "默认=./generate/proto"
            )
            ->addOption(
                "proto_path", null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "这个参数会在系统protoc命令原因附加"
            )
            ->setHelp("拼接protoc命令所需参数");
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $protobuf   = $this->getProtobuf($input);
        $goOut      = $this->getGoOut($input);
        $protoPaths = $this->getProtoPath($input);

        $protocPaths = $this->getProtobufDir($protobuf);
        $tempOut     = dirname($goOut)."/temp";

        foreach ($protocPaths as $dir) {
            $command = "protoc --proto_path={$dir}";
            foreach ($protoPaths as $protoPath) {
                if (!is_dir($protoPath)) {
                    if (is_dir(HOME_PATH.$protoPath)) {
                        $protoPath = HOME_PATH.$protoPath;
                    }
                }
                $command .= " --proto_path={$protoPath}";
            }
            $this->makeDir($tempOut);
            $command .= " --go_out={$tempOut}";
            $command .= " {$dir}/*.proto";
            echo($command), "\n";
            system($command);
        }
        $module = Go::getModule();
        system("rm -rf {$goOut}");
        $this->jsonExtWith($tempOut);
        rename($tempOut.'/'.$module."/generate/proto", $goOut);
        system("rm -rf {$tempOut}");

        return Command::SUCCESS;
    }

    protected function getProtobufDir($protobuf): array
    {
        $got = [];
        foreach (scandir($protobuf) as $value) {
            if (!in_array($value, ['.', '..'])) {
                $check = $protobuf."/".$value;
                if (is_dir($check)) {
                    // 必须有*.proto文件
                    $hasProtoFile = false;
                    foreach (scandir($check) as $checkValue) {
                        if (!in_array($checkValue, ['.', '..'])) {
                            if (is_file($check.'/'.$checkValue)
                                && pathinfo($check.'/'.$checkValue, PATHINFO_EXTENSION) == "proto") {
                                $hasProtoFile = true;
                            } else {
                                if (is_dir($check.'/'.$checkValue)) {
                                    $tempGot = $this->getProtobufDir($check.'/'.$checkValue);
                                    foreach ($tempGot as $v => $c) {
                                        $got[$v] = $c;
                                    }
                                }
                            }
                        }
                    }

                    if ($hasProtoFile) {
                        $got[$value] = $check;
                    }
                } elseif (pathinfo($check, PATHINFO_EXTENSION) == "proto") {
                    $arr            = explode('/', $protobuf);
                    $got[end($arr)] = $protobuf;
                    break;
                }
            }
        }
        return $got;
    }

    protected function getProtobuf(InputInterface $input)
    {
        $path = $input->getOption("protobuf");
        if (!$path) {
            $path = "./protobuf";
        }

        return $this->getHomePath($path);
    }

    protected function getProtoPath(InputInterface $input): array
    {
        $got        = [
            $this->getHomePath('./protobuf'),
            $this->getHomePath('./protobuf/common/http'),
        ];
        $protoPaths = $input->getOption("proto_path");
        foreach ($protoPaths as $path) {
            $temp       = $this->getHomePath($path);
            $got[$temp] = $temp;
        }
        return array_values($got);
    }

    protected function getGoOut(InputInterface $input): string
    {
        $goOut = $input->getOption("go_out");
        if (!$goOut) {
            $goOut = "./generate/proto";
        }

        return $this->getHomePath($goOut, false);
    }

    protected function getHomePath(string $path, $mkdir = true)
    {
        $path = HOME_PATH."/".$path;
        $path = str_replace("/./", "/", $path);

        if ($mkdir) {
            if (!is_dir($path)) {
                $this->makeDir($path);
            }
            return realpath($path);
        }
        return $path;
    }

    protected function makeDir(string $path)
    {
        if (!is_dir($path)) {
            echo "mkdir 0755 {$path}\n";
            mkdir($path, 0755, true);
        }
    }

    protected function jsonExtWith(string $dirCheck)
    {
        $goParser    = new GolangParser();
        foreach (DirsHelp::getDirs($dirCheck, 'go') as $file) {
            if (!strpos($file, ".pb.go")) {
                continue;
            }
            $context = file_get_contents($file);
            if (!strpos($context, $this->tagStr)) {
                continue;
            }

            $goArr  = new GolangToArray($file, $context);
            $golang = $goParser->parser($goArr);

            $fileJsonExt = $this->getJsonExt($golang->getPackageDoc());
            foreach ($golang->getType() as $type) {
                $doc         = $type->getDoc();
                $typeJsonExt = $this->getJsonExt($doc);
                $typeString  = $goArr->getFileString($type->getStartOffset(), $type->getEndOffset());
                $replace     = [];
                foreach ($type->getAttributes() as $attribute) {
                    $doc         = $attribute->getDoc();
                    $attrJsonExt = $this->getJsonExt($doc);

                    if ($fileJsonExt || $typeJsonExt || $attrJsonExt) {
                        foreach ($attribute->getTags() as $name => $tag) {
                            if ($name === 'protobuf') {
                                $old = $new = $name.':"'.$tag.'"';
                                foreach ([$attrJsonExt, $typeJsonExt, $fileJsonExt] as $ext) {
                                    if ($ext && !isset($replace[$old])) {
                                        $new = $this->getExtStr($name, $tag, $ext);
                                    }
                                }
                                if ($old != $new) {
                                    $replace[$old] = $new;
                                }
                            }
                        }
                    }
                }
                if ($replace) {
                    $newTypeString = str_replace(array_keys($replace), array_values($replace), $typeString);
                    $context       = str_replace($typeString, $newTypeString, $context);
                }
            }

            file_put_contents($file, $context);
        }
    }

    protected function getExtStr(string $name, string $tag, array $ext): string
    {
        $extName  = $ext['name'];
        $extValue = $ext['value'];
        if (!$extValue) {
            $extValue = '{name}';
        }
        $old = $name.':"'.$tag.'"';
        $new = "{$old} {$extName}:\"{$extValue}\"";

        $arr     = explode(',', $tag);
        $tagName = reset($arr);
        foreach ($arr as $item) {
            $itemArr     = explode('=', $item);
            if (count($itemArr)==2 && $itemArr[0]=='name') {
                $tagName = $itemArr[1];
            }
        }
        return str_replace(
            ['{name}'],
            [$tagName],
            $new
        );
    }

    protected function getJsonExt(string $doc): array
    {
        if (!strpos($doc, $this->tagStr)) {
            return [];
        }
        $str      = StringHelp::cutStr($this->tagStr . "(", ")", $doc);
        $tagName  = StringHelp::cutChar('"', '"', $str);
        $tagValue = StringHelp::cutChar('"', '"', substr($str, strlen($tagName)));

        $tagName  = trim($tagName, '"');
        $tagValue = trim($tagValue, '"');

        return ['name' => $tagName, 'value' => $tagValue];
    }
}