<?php


namespace App\Commands;


use App\Go;
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BeanCommand extends Command
{
    /** @var InputInterface */
    protected $input;
    /** @var OutputInterface */
    protected $output;

    /**
     * @return \App\Commands\BeanCommand
     */
    protected function configure()
    {
        return $this->setName("make:bean")
            ->setDescription("生成依赖文件")
            ->addArgument("path", InputArgument::OPTIONAL, "编排目录, 这个目录下的所有go文件的bean注释")
            ->addOption("force", "f", InputOption::VALUE_OPTIONAL, "强制刷新", false)
            ->setHelp("根据@Bean的注解, 生成依赖定义文件");
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input  = $input;
        $this->output = $output;

        $dirCheck = $input->getArgument("path");
        if (!$dirCheck) {
            $dirCheck = HOME_PATH.'/app';
        }
        $dirCheck = realpath($dirCheck);
        $output->writeln("<info>{$dirCheck} Bean ...</info>");
        if (!is_dir($dirCheck)) {
            $dirCheck2 = getcwd()."/".$dirCheck;
            if (!is_dir($dirCheck2)) {
                $output->writeln("<error>{$dirCheck} 目录不存在, 优先绝对路径</error>");
                $output->writeln("<error>{$dirCheck} 不存在会在工作目录下检查 {$dirCheck2}</error>");
                return Command::FAILURE;
            }
            $dirCheck = realpath($dirCheck2);
        } else {
            $dirCheck = realpath($dirCheck);
        }

        $goParser    = new GolangParser();
        $dirGenerate = [];
        $checkDir    = $this->getDirList($dirCheck);
        // 收集bean
        foreach (DirsHelp::getDirs($dirCheck, 'go') as $file) {
            $dir = dirname($file);
            // 跳过目录
            if (in_array($dir, $checkDir)) {
                continue;
            }
            if (pathinfo($file, PATHINFO_BASENAME) == "z_inject_gen.go") {
                continue;
            }
            $context = file_get_contents($file);
            if (!strpos($context, '@Bean')) {
                continue;
            }
            $goArr  = new GolangToArray($file, $context);
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

        return Command::SUCCESS;
    }

    /**
     * 生成依赖文件
     * @param  string  $dir
     * @param  array  $arr
     */
    protected function toGenerateDirInject(string $dir, array $arr)
    {
        $imports     = $this->changeImportAlias($arr);
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
                // 生成服务初始化
                $gen->addFunc($this->getInitializeFunc($golang, $type, $imports));
            } else {
                // 已经存在服务提供者, 自动解析所需参数
                // 生成服务初始化
                $gen->addFunc($this->getInitializeFunc($golang, $type, $imports, true));
            }

            // 变量单例缓存
            $varGen = new GoLangVar();
            $varGen->setName($type->getName()."Single");
            $varGen->setType("*".$type->getType());
            $gen->addVar($varGen);
        }

        $imports["home_constraint"] = Go::getModule()."/bootstrap/constraint";
        $gen->setImport($imports);
        $gen->push();
    }

    public function getInitializeFunc(GolangParser $golang, Type $type, array &$imports, bool $par = false): GoLangFunc
    {
        $func = new GoLangFunc();
        $code = "";

        $attrs = $type->getAttributes();
        $pars  = '';
        if ($par === false) {
            // struct 属性解析
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
        } else {
            // 函数参数解析
            $provider = "New{$type->getName()}Provider";
            foreach ($golang->getFunc() as $funcTemp) {
                if ($provider == $funcTemp->getName()) {
                    $params = $funcTemp->getParameter();
                    foreach ($params as $param) {
                        if ($param['name'] == 'nil') {
                            $pars .= "\n\t\t\tnil,\n\t\t";
                        } else {
                            if ($param['alias']) {
                                $provider = $param['alias'].".InitializeNew{$param['type']}Provider()";

                                $alias = $param['alias'];
                                if ($alias) {
                                    if (!isset($imports[$alias])) {
                                        $imports[$alias] = $golang->getImport($alias);
                                    }
                                }
                            } else {
                                $provider = "InitializeNew{$param['type']}Provider()";
                            }
                            $pars .= "\n\t\t\t{$provider},";
                        }
                    }
                    break;
                }
            }
        }
        $func->setReturns(["*".$type->getName()]);

        $pars .= $pars?"\n\t\t":"";
        $code .= "\n\tif {$type->getName()}Single == nil {";
        $code .= "\n\t\t{$type->getName()}Single = New{$type->getName()}Provider({$pars})\n";
        $code .= "\n\t\thome_constraint.AfterProvider({$type->getName()}Single)\n";
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

    /**
     * 获取所有目录列表
     *
     * @param  string  $path
     * @return array
     */
    public function getDirList(string $path): array
    {
        $force = $this->input->getOption("force") === null;
        if ($force) {
            return [];
        }
        $arr = [];
        if (is_dir($path)) {
            $dir = scandir($path);
            foreach ($dir as $value) {
                $sub_path = $path.'/'.$value;
                if (in_array($value, ['.', '..', '.git'])) {
                    continue;
                } else {
                    if (is_dir($sub_path)) {
                        $arr = array_merge($arr, $this->getDirList($sub_path));
                    }
                }
            }
            // 必须是最低部的目录, 检查这个目录是否有更新
            $genFileName = $path.'/z_inject_gen.go';
            if (file_exists($genFileName)) {
                if (filemtime($genFileName) != filemtime($path)) {
                    // 无修改需要跳过
                    $arr[] = $path;
                }
            } else {
                // 新的, 不需要跳过
            }
        }
        return $arr;
    }
}