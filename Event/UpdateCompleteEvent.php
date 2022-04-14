<?php

declare(strict_types=1);

namespace Corp\EiisBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class UpdateCompleteEvent extends Event
{
	const NAME = 'eiis.update.complete';

	protected $signalSource;

	protected $systemObjectCode;

	public function __construct(string $systemObjectCode, int $signalSource)
	{
		$this->systemObjectCode = $systemObjectCode;
		$this->signalSource = $signalSource;
	}

	public function getSystemObjectCode()
	{
		return $this->systemObjectCode;
	}
}
