<?php

namespace Corp\EiisBundle\Controller;

use Corp\EiisBundle\Entity\EiisPackage;
use Corp\EiisBundle\Entity\EiisSession;
use Corp\EiisBundle\Event\UpdateNotificationEvent;
use Corp\EiisBundle\Traits\ContainerUsageTrait;
use Corp\MyBundle\Entity\Corpemployee;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EiisServiceController
{
    use ContainerUsageTrait { assignContainer as protected; }

    /**
     * EiisServiceController constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->assignContainer($container);
    }

    /**
     * @param string $login
     * @param string $password
     * @return string
     */
    public function GetSessionId(string $login, string $password){
        if($login!==$this->getContainer()->get('eiis.service')->getConfig()['local']['username'] || $password!==$this->getContainer()->get('eiis.service')->getConfig()['local']['password']){
            return $this->getError('0321');
        }
//        throw new \Exception(':(');
        $session = new EiisSession();
        $session->setUuid($this->getContainer()->get('eiis.service')->guidv4());
        $this->getEm()->persist($session);
        $this->getEm()->flush();
        return $this->prepareResponse(['session'=>['id'=>$session->getUuid()]]);
    }

    /**
     * @param string $login
     * @param string $password
     * @return string
     */
    public function SendUpdateNotification(string $sessionId, string $systemObjectCode){
        if(!$this->isValidSession($sessionId)){
            return $this->getError('0322');
        }
        if($this->isValidRemoteCode($systemObjectCode, 'setter')){
            $event = new UpdateNotificationEvent($systemObjectCode, UpdateNotificationEvent::SIGNAL_FROM_EXTERNAL);
            $this->getContainer()->get('event_dispatcher')->dispatch('eiis.notification.update', $event);
        }else{
            return $this->getError('034');
        }
    }

    private function isValidLocalCode(string $code, string $type){
        $info = $this->getContainer()->get('eiis.service')->getConfigByLocalCode($code);
        return $info && isset($info[$type]);
    }

    private function isValidRemoteCode(string $code, string $type){
        $info = $this->getContainer()->get('eiis.service')->getConfigByRemoteCode($code);
        return $info && isset($info[$type]);
    }

    private function getError($code){
        return (string)$code;
    }

    private function prepareResponse(array $data){
        return $this->arrayToXml($data)->asXML();
    }

    /**
     * @param array $array
     * @return \SimpleXMLElement
     */
    private function arrayToXml(array $array){
        $xml = new \SimpleXMLElement('<xml/>', LIBXML_NOXMLDECL );
        foreach ($array as $key=>$value){
            if(!is_array($value)){
                throw new \Exception();
            }
            $child = $xml->addChild($key);
            foreach ($value as $k=>$val){
                $child->addAttribute($k, $val);
            }
        }
        return $xml->children();
    }

    private function isValidSession($sessionId){
        $session = $this->getEm()->getRepository(EiisSession::class)->findOneBy(['uuid'=>$sessionId]);
        return $session ? $session->getDateCreated() > new \DateTime('-1 day') : false;
    }

    /**
     * @param string $sessionId
     * @param string $objectCode
     * @param bool $historyCreate
     * @param bool $documentInclude
     * @param string $filter
     * @return string
     */
    public function CreatePackage(string $sessionId, string $objectCode, $historyCreate, $documentInclude, $filter){
        if(!$this->isValidSession($sessionId)){
            return $this->getError('0322');
        }
        if($this->isValidLocalCode($objectCode, 'getter')){
            $package = new EiisPackage();
            $package
                ->setUuid($this->getContainer()->get('eiis.service')->guidv4())
                ->setDateCreated(new \DateTime())
                ->setSystemObjectCode($objectCode);
            $this->getEm()->persist($package);
            $this->getEm()->flush($package);
            return $this->prepareResponse(['package'=>['id'=>$package->getUuid()]]);
        }else{
            return $this->getError('034');
        }
    }

    /**
     * @param string $sessionId
     * @param string $packageId
     * @param int $part
     * @return string
     */
    public function GetPackage($sessionId, $packageId, $part){
        if(!$this->isValidSession($sessionId)){
            return $this->getError('0322');
        }
        $packageId = $this->getEm()->getRepository(EiisPackage::class)->findOneBy(['uuid'=>$packageId]);
        if(!$packageId){
            return $this->getError('0541');
        }

        $info = $this->getContainer()->get('eiis.service')->getConfigByLocalCode($packageId->getSystemObjectCode());
        $list = $this->getEm()->getRepository($info['class'])->{$info['find_all_method']}();
        $data = [];
        foreach ($list as $value){
            $data[] = $value->{$info['getter']}($this->getContainer()->get('eiis.service'));
        }
        $this->getEm()->flush();
        $xml = new \SimpleXMLElement('<object/>', LIBXML_NOXMLDECL);
        foreach ($data as $value){
            $child = $xml->addChild('row');
            foreach ($value as $k=>$val){
                $childName = $k==='ID' ? 'primary':'column';
                $child->addChild($childName, $val)->addAttribute('code', $k);
            }
        }
        return $xml->asXML();
    }

    /**
     * @param string $sessionId
     * @param string $packageId
     * @return string
     */
    public function GetPackageMeta($sessionId, $packageId){
        if(!$this->isValidSession($sessionId)){
            return $this->getError('0322');
        }
        if(!$this->getEm()->getRepository(EiisPackage::class)->findOneBy(['uuid'=>$packageId])){
            return $this->getError('0541');
        }
        return $this->prepareResponse([
            'package'=>[
                'capacity'=>1,
                'id'=>$packageId,
                'size'=>strlen($this->GetPackage($sessionId, $packageId, 1))
            ]
        ]);
    }

    /**
     * @param string $sessionId
     * @param string $packageId
     * @return string
     */
    public function SetOk($sessionId, $packageId){
        if(!$this->isValidSession($sessionId)){
            return $this->getError('0322');
        }
        $package = $this->getEm()->getRepository(EiisPackage::class)->findOneBy(['uuid'=>$packageId]);
        if(!$package){
            return $this->getError('0541');
        }

        $this->getEm()->remove($package);
        $this->getEm()->flush();
    }

    /**
     * @param string $sessionId
     */
    public function GetFile($sessionId, $fileId){
        if(!$this->isValidSession($sessionId)){
            return $this->getError('0322');
        }
		$config = $this->getContainer()->get('eiis.service')->getConfig();
		return $this->getEm()->getRepository($config['getfile']['class'])->{$config['getfile']['find_method']}($fileId);
    }
}
