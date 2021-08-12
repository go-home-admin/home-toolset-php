<?php


namespace App\Commands;


use App\Go;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ProtocCommand extends Command
{
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
            $command = "protoc";
            foreach ($protoPaths as $protoPath) {
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
        rename($tempOut.'/'.$module."/generate/proto", $goOut);
        system("rm -rf {$tempOut}");

        return Command::SUCCESS;
    }

    private function getProtobufDir($protobuf): array
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
                                $tempGot = $this->getProtobufDir($check.'/'.$checkValue);
                                foreach ($tempGot as $v => $c) {
                                    $got[$v] = $c;
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

    private function getProtobuf(InputInterface $input)
    {
        $path = $input->getOption("protobuf");
        if (!$path) {
            $path = "./protobuf";
        }

        return $this->getHomePath($path);
    }

    private function getProtoPath(InputInterface $input): array
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

    private function getGoOut(InputInterface $input): string
    {
        $goOut = $input->getOption("go_out");
        if (!$goOut) {
            $goOut = "./generate/proto";
        }

        return $this->getHomePath($goOut, false);
    }

    private function getHomePath(string $path, $mkdir = true)
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

    private function makeDir(string $path)
    {
        if (!is_dir($path)) {
            echo "mkdir 0755 {$path}\n";
            mkdir($path, 0755, true);
        }
    }
}