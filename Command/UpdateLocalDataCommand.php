<?php

namespace Corp\EiisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateLocalDataCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('eiis:action')
            ->addArgument('type', InputArgument::REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($input->getArgument('type')){
            case 'eiisUpdateLocalData':
            case 'eiisUpdateExternalData':
            case 'clearOldData':
				$this->getContainer()->get('eiis.service')->setLogger(new ConsoleLogger($output));
				$this->getContainer()->get('eiis.service')->{$input->getArgument('type')}();
				break;
            default:
                throw new \Exception('Wrong type');
        }
    }
}
