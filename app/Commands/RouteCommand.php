<?php


namespace App\Commands;


use App\ProtoHelp\MakeGrpc;
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
            ->addOption(
                "grpc", null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                "对service转grpc中间文件架列表"
            )
            ->setDescription("生成路由源码");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        foreach (scandir(HOME_PATH.'/routes') as $file) {
            if (!in_array($file, ['.', '..', '.gitignore'])) {
                unlink(HOME_PATH.'/routes/'.$file);
            }
        }

        $help = $this->readAllProtoc();
        $help->makeRoute();
        $help->makeRouteConfig();

        $grpcList = $input->getOption("grpc");
        if ($grpcList) {
            // 如果有grpc选项, 同时生成grpc调用代码
            $this->makeGrpc($help, $grpcList);
        }

        return Command::SUCCESS;
    }

    private function makeGrpc(Proto $help, array $grpcList)
    {
        if (!$grpcList){
            return;
        }

        $pars = [];
        foreach ($grpcList as $path) {
            $path = HOME_PATH.'/protobuf/'.$path;
            foreach ($help->getAllPackage() as $pack=>$arr){
                if ( strpos($pack, $path)===0 ) {
                    foreach ($arr as $item){
                        $pars[] = $item;
                    }
                }
            }
        }
        unset($item, $path, $grpcList, $arr, $pack);
        /** @var ProtoParser $proto */
        foreach ($pars as $proto){
            MakeGrpc::makeGrpcService($proto);
        }
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