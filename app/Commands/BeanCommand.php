<?php


namespace App\Commands;


use GoLang\Parser\FileParser\Type;
use GoLang\Parser\Generate\GoLangFile;
use GoLang\Parser\Generate\GoLangFunc;
use GoLang\Parser\Generate\GoLangVar;
use GoLang\Parser\GolangParser;
use GoLang\Parser\GolangToArray;
use ProtoParser\DirsHelp;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BeanCommand extends Command
{
    /**
     * @return \App\Commands\BeanCommand
     */
    protected function configure()
    {
        return $this->setName("make:bean")
            ->setDescription("生成依赖文件")
            ->addArgument("path", InputArgument::REQUIRED, "编排目录, 这个目录下的所有go文件的bean注释")
            ->setHelp("根据@Bean的注解, 生成依赖定义文件");
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dirCheck = $input->getArgument("path");
        if (!is_dir($dirCheck)) {
            $dirCheck2 = getcwd() ."/". $dirCheck;
            if (!is_dir($dirCheck2)) {
                $output->writeln("<error>{$dirCheck} 目录不存在, 优先绝对路径</error>");
                $output->writeln("<error>{$dirCheck} 不存在会在工作目录下检查 {$dirCheck2}</error>");
                return Command::FAILURE;
            }
            $dirCheck = realpath($dirCheck2);
        }else{
            $dirCheck = realpath($dirCheck);
        }

        $goParser    = new GolangParser();
        $dirGenerate = [];
        // 收集bean
        foreach (DirsHelp::getDirs($dirCheck, 'go') as $file) {
            $dir    = dirname($file);
            $goArr  = new GolangToArray($file);
            $golang = $goParser->parser($goArr);

            foreach ($golang->getType() as $type) {
                $doc = $type->getDoc();
                if ($doc && $type->isStruct() && strpos($doc, '@Bean')) {
                    $dirGenerate[$dir][$type->getName()] = [$golang, $type];
                }
            }
        }

        foreach ($dirGenerate as $dir => $item) {
            $this->toGenerateDirInject($dir, $item);
        }

        $output->writeln("<info>{$dirCheck} OK</info>");
        return Command::SUCCESS;
    }

    /**
     * 生成依赖文件
     * @param  string  $dir
     * @param  array  $arr
     */
    protected function toGenerateDirInject(string $dir, array $arr)
    {
        $imports = $this->changeImportAlias($arr);

        $genFileName = $dir.'/z_inject_gen.go';
        $gen         = new GoLangFile($genFileName);

        foreach ($arr as $item) {
            /**
             * @var GolangParser $golang
             * @var \GoLang\Parser\FileParser\Type $type
             */
            list($golang, $type) = $item;
            // 包
            $gen->setPackage($golang->getPackage());

            // 生成服务提供
            if (!$golang->hasFunc("New{$type->getName()}Provider")) {
                $gen->addFunc($this->getProviderFunc($golang, $type));
            }

            // 生成服务初始化
            $gen->addFunc($this->getInitializeFunc($golang, $type));

            // 变量单例缓存
            $varGen = new GoLangVar();
            $varGen->setName($type->getName()."Single");
            $varGen->setType("*".$type->getType());
            $gen->addVar($varGen);
        }

        $gen->setImport($imports);
        $gen->push();
    }

    public function getInitializeFunc(GolangParser $golang, Type $type): GoLangFunc
    {
        $func = new GoLangFunc();
        $code = "";

        $attrs = $type->getAttributes();
        $pars  = '';
        foreach ($attrs as $attr) {
            $tags = $attr->getTags();
            if (isset($tags["inject"])) {
                if ($attr->getStructAlias()) {
                    $provider = $attr->getStructAlias().".InitializeNew{$attr->getStruct()}Provider()";
                } else {
                    $provider = "InitializeNew{$attr->getStruct()}Provider()";
                }

                $pars .= "\n\t\t\t{$provider},";
            }
        }
        $func->setReturns(["*".$type->getName()]);


        $code .= "\n\tif {$type->getName()}Single == nil {";
        $code .= "\n\t\t{$type->getName()}Single = New{$type->getName()}Provider({$pars}\n\t\t)\n";
        $code .= "\t}\n";
        $code .= "\n\treturn {$type->getName()}Single\n";
        $func->setCode($code);
        $func->setName("InitializeNew{$type->getName()}Provider");
        return $func;
    }

    /**
     * 提供者函数
     * @param  \GoLang\Parser\GolangParser  $golang
     * @param  \GoLang\Parser\FileParser\Type  $type
     * @return \GoLang\Parser\Generate\GoLangFunc
     */
    public function getProviderFunc(GolangParser $golang, Type $type): GoLangFunc
    {
        $func  = new GoLangFunc();
        $code  = "\n    {$type->getName()} := &{$type->getName()}{}";
        $attrs = $type->getAttributes();

        $pars = [];
        foreach ($attrs as $attr) {
            $tags = $attr->getTags();
            if (isset($tags["inject"])) {
                $par = $attr->getName();
                if ($attr->getStructAlias()) {
                    $par .= ($attr->isPointer() ? " *" : " ")."{$attr->getStructAlias()}.{$attr->getStruct()}";
                } else {
                    $par .= ($attr->isPointer() ? " *" : " ")."{$attr->getStruct()}";
                }
                $pars[] = $par;
                $code   .= "\n    {$type->getName()}.{$attr->getName()} = {$attr->getName()}";
            }
        }
        $func->setParameter($pars);
        $func->setReturns(["*".$type->getName()]);

        $code .= "\n    return {$type->getName()}\n";
        $func->setCode($code);
        $func->setName("New{$type->getName()}Provider");
        return $func;
    }

    /**
     * 对整个目录的引入进行别名检查和修改冲突
     * @param  array  $arr
     * @return array
     */
    protected function changeImportAlias(array $arr): array
    {
        $imports    = [];
        $aliasCount = [];
        foreach ($arr as $item) {
            /**
             * @var GolangParser $golang
             * @var \GoLang\Parser\FileParser\Type $type
             */
            list($golang, $type) = $item;
            $attrs = $type->getAttributes();
            foreach ($attrs as $attr) {
                $tags = $attr->getTags();
                if (isset($tags["inject"])) {
                    $alias = $attr->getStructAlias();
                    if ($alias) {
                        $import = $golang->getImport($alias);
                        if ($import) {
                            if (!isset($imports[$alias])) {
                                $imports[$alias] = $import;
                            } else {
                                if ($imports[$alias] != $import) {
                                    for ($i = 1; $i <= 100; $i++) {
                                        $newAlias = $alias."_{$i}";
                                        if (!isset($aliasCount[$alias][$i]) && !$golang->getImport($newAlias)) {
                                            $golang->changeAliasName($alias, $newAlias);

                                            $aliasCount[$alias][$i] = $import;
                                            $imports[$newAlias]     = $import;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $imports;
    }
}