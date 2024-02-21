<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 2018-04-20
 * Time: 16:06
 */

namespace Corp\EiisBundle\Service;

use Corp\EiisBundle\Entity\EiisLog;
use Corp\EiisBundle\Entity\EiisSession;
use Corp\EiisBundle\Entity\EiisUpdateNotification;
use Corp\EiisBundle\Event\UpdateNotificationEvent;
use Corp\EiisBundle\Event\UpdateCompleteEvent;
use Corp\EiisBundle\Exceptions\SkipThisObjectException;
use Corp\EiisBundle\Interfaces\IEiisLog;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Laminas\Soap\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EiisIntegrationService
{
	private $client;

	private $config;

	/** @var \DateTime */
	private $date;

	/** @var LoggerInterface */
	private $logger;

    public function __construct(private readonly ManagerRegistry $doctrine,
                                private readonly EventDispatcherInterface $eventDispatcher,
                                private readonly ValidatorInterface $validator)
    {
    }

    protected function getEm(): EntityManagerInterface
    {
        return $this->doctrine->getManager();
    }

    public function setConfig(array $config)
    {
		$this->config = $config;
	}

	public function getConfig()
    {
		return $this->config;
	}

	public function updateLocalDataByCode(string $code)
    {
		$this->date = new \DateTime();
		$sessionId = $this->getSessionId();
        $filter = $this->filterGenerate($code);
		$packageId = (string)$this->prepareResult($this->getClient()->CreatePackage(['sessionId'=>$sessionId,'objectCode'=>$code,'historyCreate'=>false,'documentInclude'=>false,'filter'=>$filter])->CreatePackageResult)->attributes()->id;
		$part = 1;
		while (true){
			$this->getLogger()->info('Load '.$code.' package part #'.$part);
			$result = $this->handlePackagePart($code, $sessionId, $packageId, $part);
			if(!$result){
				break;
			}
			$part++;
		}
		$this->eventDispatcher->dispatch(new UpdateCompleteEvent($code, UpdateNotificationEvent::SIGNAL_FROM_EXTERNAL), UpdateCompleteEvent::NAME);
	}

    private function filterGenerate(string $code)
    {
        $filter = new \SimpleXMLElement('<filter/>');
        $filter = $filter->asXML();

        $configItem = $this->getConfigByRemoteCode($code);

        try{

            if($configItem && isset($configItem['filter'])){
                $filtData = $configItem['filter'];
                $value = $filtData['value'];
                if($filtData['type'] === 'DATE'){
                    $filtDate = new \DateTime($filtData['value']);
                    $value = $filtDate->format('d.m.Y');
                }

                $filter = '<filter><column code="'.$filtData['field'].'" '.$filtData['action'].'="'.$value.'"></column></filter>';
            }
        }catch (Exception $e){
            $this->getLogger()->warning('Generating Filter  is error('.$code.'):'.$e->getMessage());
        }

        return $filter;
    }

	private function handlePackagePart($code, $sessionId, $packageId, $part)
    {
		$i = 0;
		while(true){
			sleep(10);
			$i++;
			if($i > 10){
				throw new \Exception('Не удалось получить пакет данных для объекта '.$code);
			}

			$package = false;
			try{
				$package = $this->getClient()->GetPackage(['sessionId'=>$sessionId,'packageId'=>$packageId,'part'=>$part]);
			}catch (\Throwable $exception){
				$this->getLogger()->info('Error "'.$exception->getMessage().'" next try #'.$i);
			}

			if($package){
				switch ((string)$package->GetPackageResult){
					case '0542':
						return false;
					case '053':
						$this->getLogger()->info(date('c').' 053 code - next try #'.$i);
						continue 2;
					default:
						break 2;
				}
			}
		}
		try{
            $this->getLogger()->debug((string)$code.' #'.$packageId);
            $this->getLogger()->debug((string)$package->GetPackageResult);
			$data = simplexml_load_string((string)$package->GetPackageResult, \SimpleXMLElement::class, LIBXML_COMPACT);
		}catch (\Throwable $e){
			throw $e;
		}

		try{
			$config = $this->getConfigByRemoteCode($code);
			if(!$config){
				throw new \Exception('Config for code '.$code.' not found');
			}
			$this->applyData($this->object2array($data), $config);
			if($config['delete_object_supported']){
				$this->getQb()
					->delete($config['class'],'t')
					->where('t.eiisId is null or t.eiisId=\'\'')
					->getQuery()
					->execute();
			}else{
				$this->getLogger()->warning('Delete object not supported for class '.$config['class']);
			}

			$this->getEm()->flush();
		}catch (\Throwable $e){
			throw $e;
		}
		return true;
	}

	public function eiisUpdateLocalData()
    {
		$updateNotifications = $this->getEm()->getRepository(EiisUpdateNotification::class)->findBy(['signalFrom'=>UpdateNotificationEvent::SIGNAL_FROM_EXTERNAL]);
		foreach ($updateNotifications as $notification){
			$this->updateLocalDataByCode($notification->getSystemObjectCode());
		}
	}

	public function eiisUpdateExternalData()
    {
		$updateNotifications = $this->getEm()->getRepository(EiisUpdateNotification::class)->findBy(['signalFrom'=>UpdateNotificationEvent::SIGNAL_FROM_INTERNAL]);
		foreach ($updateNotifications as $notification){
			$this->sendUpdateNotification($this->getSessionId(), $notification->getSystemObjectCode());
		}
	}

	public function getConfigByRemoteCode(string $remoteObjectCode)
    {
		foreach ($this->getConfig()['objects'] as $val){
			if($remoteObjectCode===$val['remote_code']){
				return $val;
			}
		}
		return null;
	}

	public function getConfigByLocalCode(string $localObjectCode)
    {
		foreach ($this->getConfig()['objects'] as $val){
			if($localObjectCode===$val['local_code']){
				return $val;
			}
		}
		return false;
	}

    private function getClient()
    {
        if(!$this->client){
            $this->client = new \Laminas\Soap\Client($this->getConfig()['remote']['url'],
                [
                    'login'=>$this->getConfig()['remote']['username'],
                    'password'=>$this->getConfig()['remote']['password']
                ]);

        }
        return $this->client;

    }

	private function getSessionId()
    {
		return (string)$this->prepareResult($this->getClient()->GetSessionId(
			[
				'login'=>$this->getConfig()['remote']['username'],
				'password'=>$this->getConfig()['remote']['password']
			])->GetSessionIdResult)->attributes()->id;
	}

	private function sendUpdateNotification(string $sessionId, string $systemObjectCode)
    {
		$result = $this->prepareResult((string)$this->getClient()->SendUpdateNotification(['sessionId'=>$sessionId,'systemObjectCode'=>$systemObjectCode])->SendUpdateNotificationResult);
		switch ($result){
			case '':
				break;
			default:
				throw new \Exception('Wrong Eiis response: '.$result);
		}
	}

	private function applyData(array $data, array $config)
    {

		$notCreatedCount = 0;
		foreach ($data as $value){
            $newObject = false;
			$obj = $this->getEm()->getRepository($config['class'])->{$config['find_one_method']}($value);

			if(!$obj){
				if($config['create_object_supported']){
					$obj = new $config['class']();
					if (method_exists($obj, 'setServiceContainer'))
                    {
                        $obj->setServiceContainer($this->container);
                    }

                    if (method_exists($obj, 'setDoctrine'))
                    {
                        $obj->setDoctrine($this->doctrine);
                    }
                    
                    $newObject = true;
				}else{
					$notCreatedCount++;
					continue;
				}
			}
			try {
				$logs = $obj->{$config['setter']}($value);
			}catch (SkipThisObjectException $exception){
				$notCreatedCount++;
				if(!$obj || is_null($obj->getId())){
				    $this->getEm()->detach($obj);
				}else{
				    $this->getEm()->refresh($obj);
				}
				continue;
			}
			$errors = $this->validator->validate($obj);
			if($errors->count() > 0){
				$message = [];
				/** @var ConstraintViolationInterface $error */
				foreach ($errors as $error){
					$message[] = $error->getPropertyPath().' '.$error->getMessage();
					$this->getLogger()->warning('CREATE '.$config['class'].'#'.$obj->getEiisid().' '.$error->getPropertyPath().' '.$error->getMessage());
				}
				unset($obj);
				continue;
			}

            $this->getEm()->persist($obj);
		}
		if($notCreatedCount > 0){
			$this->getLogger()->warning('Not Created Count: '.$notCreatedCount);
		}
	}

	private function object2array($object)
    {
		$data = [];
		$key = 0;
		foreach ($object->row as $value){
			$data[$key] = ['EiisId'=>(string)$value->primary];
			foreach ($value->column as $column){
				$data[$key][(string)$column->attributes()->code] = (string)$column;
			}
			$key++;
		}
		return $data;
	}

	private function prepareResult(string $xml)
    {
		switch ($xml){
			case '0320':
				$message = 'IP и MAC адреса не соответствуют открытой сессии.';
				break;
			case '0321':
				$message = 'Неверный логин или пароль.';
				break;
			case '0322':
				$message = 'Неверный идентификатор сессии.';
				break;
			case '033':
				$message = 'Информация по объекту недоступна.  Выводится, когда нет опубликованной версии объекта.';
				break;
			case '034':
				$message = 'Объект не объявлен. Выводится в случае, если описание объекта отсутствует в системе.';
				break;
			case '035':
				$message = 'Недостаточно прав для доступа к объекту.';
				break;
			case '0540':
				$message = 'Нет записей в объекте.';
				break;
			case '0541':
				$message = 'Пакет не найден.';
				break;
			case '0542':
				$message = 'Не найдена часть пакета.';
				break;
			case '053':
				$message = 'Пакет не сформирован.';
				break;
			case '064':
				$message = 'Нарушена последовательность применения обновлений. Есть обновления с более ранней датой.';
				break;
			case '074':
				$message = 'Информация временно недоступна. Выводится в случае обработки транзитных запросов и отсутствии подключения к системе-поставщику.';
				break;
			case '100':
				$message = 'Внутренняя ошибка системы. Выводится в случае отказа ключевых узлов ЕИИС, например, отсутствует соединение с базой данных.';
				break;
			default:
				return simplexml_load_string($xml);
		}
		throw new \Exception($message);
	}

	public function guidv4()
    {
		return $this->doctrine->getConnection()->fetchColumn('select uuid()');
	}

	/**
	 * @return LoggerInterface
	 */
	private function getLogger(): LoggerInterface
	{
		return $this->logger;
	}

	/**
	 * @param LoggerInterface $logger
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
