<?php


namespace App\Commands;


use App\Go;
use App\ProtoHelp\MakeGrpc;
use App\ProtoHelp\Proto;
use GoLang\Parser\GolangParser;
use GoLang\Parser\GolangToArray;
use ProtoParser\DirsHelp;
use ProtoParser\ProtoParser;
use ProtoParser\ProtoToArray;
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
            ->addOption(
                "grpc", null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "对service转grpc中间文件架列表"
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
        $tempOut    = dirname($goOut)."/temp";

        $protocPaths  = $this->getProtobufDir($protobuf);
        $grpcList     = $input->getOption("grpc");
        $grpcPathList = [];
        if ($grpcList) {
            // 删除上次生成的目录
            system("rm -rf ".HOME_PATH."/generate/grpc/");
            $grpcPathList = $this->grpc($protocPaths, $grpcList);
        }
        unset($grpcList);
        // api类型的protoc
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
            if (isset($grpcPathList[$dir])) {
                $commandGrpc = $command;
                $commandGrpc .= " --proto_path={$grpcPathList[$dir]}";
                $commandGrpc .= " --go_out=plugins=grpc:{$tempOut}";
                $commandGrpc .= " {$grpcPathList[$dir]}/*.proto";
                echo($commandGrpc), "\n";
                system($commandGrpc);
            }

            $command .= " --go_out={$tempOut}";
            $command .= " {$dir}/*.proto";
            echo($command), "\n";
            system($command);
        }

        $module = Go::getModule();
        // 清空旧目录
        system("rm -rf {$goOut}");
        rename($tempOut.'/'.$module."/generate/proto", $goOut);
        // 删除临时目录
        system("rm -rf {$tempOut}");
        $this->jsonExtWith($goOut);

        return Command::SUCCESS;
    }

    /**
     * @param  array  $protocPaths  需要执行的目录
     * @param  array  $grpcList
     * @return array
     */
    protected function grpc(array $protocPaths, array $grpcList): array
    {
        $parser = new ProtoParser();
        $got    = [];
        foreach ($protocPaths as $protocPath) {
            foreach (scandir($protocPath) as $file) {
                if (in_array($file, ['.', '..']) || pathinfo($file, PATHINFO_EXTENSION) != 'proto') {
                    continue;
                }
                $file = $protocPath.'/'.$file;
                $file = realpath($file);

                $optPath = $this->getOpt($file);
                if (!in_array($optPath, $grpcList)) {
                    continue;
                }

                $content     = file_get_contents($file);
                $parserToArr = new ProtoToArray($content);
                $fileParser  = $parser->parser($parserToArr, $file);

                $genRpcDir = MakeGrpc::makeProtoFile($fileParser);
                if ($genRpcDir) {
                    $got[$protocPath] = $genRpcDir;
                }
            }
        }
        return $got;
    }

    /**
     * 第一个目录
     * @param  string  $file
     * @return string
     */
    protected function getOpt(string $file): string
    {
        $arr = explode(HOME_PATH.'/protobuf/', dirname($file));
        $arr = end($arr);
        $arr = explode('/', $arr);
        return reset($arr);
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
            // $this->getHomePath('./protobuf'),
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
        foreach (DirsHelp::getDirs($dirCheck, 'go') as $file) {
            $md5      = md5_file($file);
            $info     = pathinfo($file);
            $dirname  = $info['dirname'];
            $basename = $info['basename'];

            $cacheDir = str_replace([HOME_PATH.'/generate/proto'], [HOME_PATH.'/generate/proto_cache'], $dirname);
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            $cacheFile = $cacheDir.'/'.$basename.'_'.$md5;
            if (file_exists($cacheFile)) {
                file_put_contents($file, file_get_contents($cacheFile));
                continue;
            }
            $this->updateExt($file);
            file_put_contents($cacheFile, file_get_contents($file));
        }
    }

    public function updateExt(string $file)
    {
        $goParser = new GolangParser();
        if (!strpos($file, ".pb.go")) {
            return;
        }
        $context = file_get_contents($file);
        if (!strpos($context, $this->tagStr)) {
            return;
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
                $run         = [];
                if ($fileJsonExt || $typeJsonExt || $attrJsonExt) {
                    foreach ($attribute->getTags() as $name => $tag) {
                        if ($name === 'protobuf') {
                            $old = $new = $name.':"'.$tag.'"';
                            foreach ([$attrJsonExt, $typeJsonExt, $fileJsonExt] as $exts) {
                                foreach ($exts as $ext) {
                                    if ($ext && !isset($replace[$old]) && !isset($run[$ext['name']])) {
                                        $new               = $this->getExtStr($tag, $ext, $new);
                                        $run[$ext['name']] = true;
                                    }
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

    protected function getExtStr(string $tag, array $ext, string $old): string
    {
        $extName  = $ext['name'];
        $extValue = $ext['value'];
        if (!$extValue) {
            $extValue = '{name}';
        }
        $new = "{$old} {$extName}:\"{$extValue}\"";

        $arr     = explode(',', $tag);
        $tagName = reset($arr);
        foreach ($arr as $item) {
            $itemArr = explode('=', $item);
            if (count($itemArr) == 2 && $itemArr[0] == 'name') {
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
        $got    = [];
        $docArr = explode("//", $doc);
        foreach ($docArr as $doc) {
            if (strpos($doc, $this->tagStr) === false) {
                continue;
            }
            $str      = StringHelp::cutStr($this->tagStr."(", ")", $doc);
            $tagName  = StringHelp::cutChar('"', '"', $str);
            $tagValue = StringHelp::cutChar('"', '"', substr($str, strlen($tagName)));

            $tagName  = trim($tagName, '"');
            $tagValue = trim($tagValue, '"');

            $got[] = ['name' => $tagName, 'value' => $tagValue];
        }

        return $got;
    }
}