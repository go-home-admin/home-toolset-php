<?php


namespace App\Commands;


use GoLang\Parser\Generate\GoLangFile;
use GoLang\Parser\Generate\GoLangFunc;
use GoLang\Parser\GolangParser;
use GoLang\Parser\GolangToArray;
use ProtoParser\DirsHelp;
use Symfony\Component\Console\Command\Command;
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
            ->setHelp("根据@Bean的注解, 生成依赖定义文件");
    }

    /**
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $dirCheck = ALL_PROJECT."/home-admin";
        $goParser = new GolangParser();

        $dirGenerate = [];

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

        return Command::SUCCESS;
    }

    protected function toGenerateDirInject(string $dir, array $arr)
    {
        $genFileName = $dir.'/inject_gen.go';
        $gen = new GoLangFile($genFileName);

        foreach ($arr as $item) {
            /**
             * @var GolangParser $golang
             * @var \GoLang\Parser\FileParser\Type $type
             */
            list($golang, $type) = $item;
            $gen->setPackage($golang->getPackage());

            $func = new GoLangFunc();
            $gen->addFunc($func);
        }

        $gen->push();
    }
}