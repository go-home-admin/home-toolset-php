<?php


namespace App\Commands;


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
        $dir = ALL_PROJECT."/home-admin";
        foreach (DirsHelp::getDirs($dir, 'go') as $file) {
            $goArr    = new GolangToArray($file);
            $goParser = new GolangParser();
            $golang   = $goParser->parser($goArr);

            var_dump($golang);
            die;
        }

        $output->writeln("OK");
        return Command::SUCCESS;
    }
}