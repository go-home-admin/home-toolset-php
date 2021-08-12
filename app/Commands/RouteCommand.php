<?php


namespace App\Commands;


use App\ProtoHelp\Proto;
use ProtoParser\DirsHelp;
use ProtoParser\ProtoParser;
use ProtoParser\ProtoToArray;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RouteCommand extends Command
{
    protected $input;
    protected $output;

    protected function configure()
    {
        return $this->setName("make:route")
            ->addOption(
                "proto_path", null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "依赖proto路径"
            )
            ->setDescription("生成路由源码");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $help = $this->readAllProtoc();
        $help->makeRoute();
        $help->makeRouteConfig();

        return Command::SUCCESS;
    }

    private function readAllProtoc(): Proto
    {
        $parser     = new ProtoParser();
        $protoPaths = $this->getProtoPath();
        foreach ($protoPaths as $protoPath) {
            foreach (DirsHelp::getDirs($protoPath, 'proto') as $file) {
                $file        = realpath($file);
                $content     = file_get_contents($file);
                $parserToArr = new ProtoToArray($content);
                $parser->parser($parserToArr, $file);
            }
        }

        return new Proto($parser);
    }

    private function getProtoPath(): array
    {
        $got        = [
            $this->getHomePath('./protobuf'),
            $this->getHomePath('./protobuf/common/http'),
        ];
        $protoPaths = $this->input->getOption("proto_path");
        foreach ($protoPaths as $path) {
            $temp       = $this->getHomePath($path, false);
            $got[$temp] = $temp;
        }
        return array_values($got);
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