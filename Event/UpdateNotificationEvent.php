<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 2018-04-26
 * Time: 17:30
 */
namespace Corp\EiisBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class UpdateNotificationEvent extends Event
{
    const NAME = 'eiis.notification.update';
    const SIGNAL_FROM_INTERNAL = 0;
    const SIGNAL_FROM_EXTERNAL = 1;

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

    public function getSignalSource()
    {
        return $this->signalSource;
    }
}
