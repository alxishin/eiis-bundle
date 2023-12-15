<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 2018-04-26
 * Time: 17:34
 */

namespace Corp\EiisBundle\Event;

use Corp\EiisBundle\Entity\EiisUpdateNotification;
use Corp\EiisBundle\Traits\ContainerUsageTrait;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UpdateNotificationSubscriber
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    public function onEiisNotificationUpdate(UpdateNotificationEvent $event){
        $obj = $this->doctrine->getManager()->getRepository(EiisUpdateNotification::class)->findOneBy(['signalFrom'=>$event->getSignalSource(),'systemObjectCode'=>$event->getSystemObjectCode()]) ?? (new EiisUpdateNotification());
        $obj
            ->setSystemObjectCode($event->getSystemObjectCode())
            ->setSignalFrom($event->getSignalSource());
        $this->doctrine->getManager()->persist($obj);
        $this->doctrine->getManager()->flush($obj);
    }
}
