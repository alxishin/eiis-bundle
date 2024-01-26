<?php

namespace Corp\EiisBundle\Command;

use Corp\EiisBundle\Controller\EiisServiceController;
use Corp\EiisBundle\Service\EiisIntegrationService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateLocalDataCommand extends Command
{
    use LockableTrait;

    public function __construct(EiisIntegrationService $eiisIntegrationService, string $name = null)
    {
        $this->eiisIntegrationService = $eiisIntegrationService;
        parent::__construct($name);
    }


    protected function configure(): void
    {
        $this
            ->setName('eiis:action')
            ->addArgument('type', InputArgument::REQUIRED)
			->addOption('code', 'c' ,InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->lock()) {
            $io = new SymfonyStyle($input, $output);
            $io->error('The command is already running in another process.');
            return self::FAILURE;
        }
        $this->eiisIntegrationService->setLogger(new ConsoleLogger($output));
        switch ($input->getArgument('type')){
            case 'eiisUpdateLocalData':
            case 'eiisUpdateExternalData':
                $this->eiisIntegrationService->{$input->getArgument('type')}();
				break;
            case 'updateLocalDataByCode':
				if(!$input->getOption('code')){
					throw new \Exception('Option code is required');
				}
                $this->eiisIntegrationService->{$input->getArgument('type')}($input->getOption('code'));
				break;
            default:
                throw new \Exception('Wrong type');
        }

        return self::SUCCESS;
    }
}
