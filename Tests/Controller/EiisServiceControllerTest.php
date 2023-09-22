<?php

namespace Corp\EiisBundle\Tests\Controller;

use Corp\EiisBundle\Entity\EiisUpdateNotification;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EiisServiceControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $exists = $this->getContainer()->get('doctrine')->getManager()->getRepository(EiisUpdateNotification::class)->findOneBy(['systemObjectCode'=>'IIS.EMPLOYEE','signalFrom'=>0]);
        if(!$exists){
            $notify = (new EiisUpdateNotification())->setSystemObjectCode('IIS.EMPLOYEE')->setSignalFrom(0);
            $this->getContainer()->get('doctrine')->getManager()->persist($notify);
        }
        $exists = $this->getContainer()->get('doctrine')->getManager()->getRepository(EiisUpdateNotification::class)->findOneBy(['systemObjectCode'=>'IIS.EMPLOYEE','signalFrom'=>1]);
        if(!$exists){
            $notify = (new EiisUpdateNotification())->setSystemObjectCode('IIS.EMPLOYEE')->setSignalFrom(1);
            $this->getContainer()->get('doctrine')->getManager()->persist($notify);
        }
        $this->getContainer()->get('doctrine')->getManager()->flush();
        $this->eiisIntegrationService->eiisUpdateLocalData();
//        $this->getContainer()->get('eiis.service')->eiisUpdateExternalData();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(){
        $this->assertTrue($this->client->getContainer() instanceof ContainerInterface);
        return $this->client->getContainer();
    }

    protected function setUp()
    {
        $this->client = $this->createClient(array('debug'=>true));
        $this->client->followRedirects();
    }

    protected function getClient(){
        return $this->client;
    }
}
