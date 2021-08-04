<?php


namespace App\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SwaggerCommand extends Command
{
    protected function configure()
    {
        return $this->setName("make:swagger")
            ->setDescription("生成文档");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("OK");
        return Command::SUCCESS;
    }
}