<?php

namespace Corp\EiisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;

class UpdateLocalDataCommand extends ContainerAwareCommand
{
    use LockableTrait;

    protected function configure()
    {
        $this
            ->setName('eiis:action')
            ->addArgument('type', InputArgument::REQUIRED)
			->addOption('code', 'c' ,InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
	if (!$this->lock()) {
		$io = new SymfonyStyle($input, $output);
		$io->error('The command is already running in another process.');
		return 1;
	}
	$this->getContainer()->get('eiis.service')->setLogger(new ConsoleLogger($output));
        switch ($input->getArgument('type')){
            case 'eiisUpdateLocalData':
            case 'eiisUpdateExternalData':
            case 'clearOldData':
				$this->getContainer()->get('eiis.service')->{$input->getArgument('type')}();
				break;
            case 'updateLocalDataByCode':
				if(!$input->getOption('code')){
					throw new \Exception('Option code is required');
				}
				$this->getContainer()->get('eiis.service')->{$input->getArgument('type')}($input->getOption('code'));
				break;
            default:
                throw new \Exception('Wrong type');
        }
    }
}
