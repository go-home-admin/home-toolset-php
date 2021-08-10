<?php


namespace App\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RouteCommand extends Command
{
    protected function configure()
    {
        return $this->setName("make:route")
            ->setDescription("生成路由源码");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("OK");
        return Command::SUCCESS;
    }
}