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
use Corp\EiisBundle\Traits\ContainerUsageTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

class EiisIntegrationService
{
	use ContainerUsageTrait;

	private $client;

	private $config;

	/** @var \DateTime */
	private $date;

	/** @var LoggerInterface */
	private $logger;

	public function setConfig(array $config){
		$this->config = $config;
	}

	public function getConfig(){
		return $this->config;
	}

	public function eiisUpdateLocalData(){
		$this->date = new \DateTime();
		$updateNotifications = $this->getEm()->getRepository(EiisUpdateNotification::class)->findBy(['signalFrom'=>UpdateNotificationEvent::SIGNAL_FROM_EXTERNAL]);
		foreach ($updateNotifications as $notification){
			$sessionId = $this->getSessionId();
			$filter = new \SimpleXMLElement('<filter/>');
			$filter = $filter->asXML();
			$packageId = (string)$this->prepareResult($this->getClient()->CreatePackage(['sessionId'=>$sessionId,'objectCode'=>$notification->getSystemObjectCode(),'historyCreate'=>false,'documentInclude'=>false,'filter'=>$filter])->CreatePackageResult)->attributes()->id;
			$i = 0;
			while(true){
				sleep(5);
				$package = $this->getClient()->GetPackage(['sessionId'=>$sessionId,'packageId'=>$packageId,'part'=>1]);
				if((string)$package->GetPackageResult!=='053'){
					break;
				}
				if($i > 10){
					throw new \Exception('Не удалось получить пакет данных для объекта '.$notification->getSystemObjectCode());
				}
				$i++;
				$this->getLogger()->info('Try #'.$i);
			}
			try{
				$data = simplexml_load_string((string)$package->GetPackageResult, \SimpleXMLElement::class, LIBXML_COMPACT);
			}catch (\Throwable $e){
				throw $e;
			}
			$this->getEm()->beginTransaction();
			try{
				$config = $this->getConfigByRemoteCode($notification->getSystemObjectCode());
				if(!$config){
					throw new \Exception('Config for code '.$notification->getSystemObjectCode().' not found');
				}
				$this->applyData($this->object2array($data), $config);
				if($config['delete_object_supported']){
					$this->getQb()
						->delete($config['class'],'t')
//						->from()
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
			$this->getEm()->commit();

		}
	}

	public function eiisUpdateExternalData(){
		$updateNotifications = $this->getEm()->getRepository(EiisUpdateNotification::class)->findBy(['signalFrom'=>UpdateNotificationEvent::SIGNAL_FROM_INTERNAL]);
		foreach ($updateNotifications as $notification){
			$this->sendUpdateNotification($this->getSessionId(), $notification->getSystemObjectCode());
		}
	}

	public function getConfigByRemoteCode(string $remoteObjectCode){
		foreach ($this->getConfig()['objects'] as $val){
			if($remoteObjectCode===$val['remote_code']){
				return $val;
			}
		}
		return null;
	}

	public function getConfigByLocalCode(string $localObjectCode){
		foreach ($this->getConfig()['objects'] as $val){
			if($localObjectCode===$val['local_code']){
				return $val;
			}
		}
		return false;
	}

	private function getClient(){
		if(!$this->client){
			$this->client = new \Zend\Soap\Client($this->getConfig()['remote']['url']);
		}
		return $this->client;

	}

	private function getSessionId(){
		return (string)$this->prepareResult($this->getClient()->GetSessionId(
			[
				'login'=>$this->getConfig()['remote']['username'],
				'password'=>$this->getConfig()['remote']['password']
			])->GetSessionIdResult)->attributes()->id;
	}

	private function sendUpdateNotification(string $sessionId, string $systemObjectCode){
		$result = $this->prepareResult((string)$this->getClient()->SendUpdateNotification(['sessionId'=>$sessionId,'systemObjectCode'=>$systemObjectCode])->SendUpdateNotificationResult);
		switch ($result){
			case '':
				break;
			default:
				throw new \Exception('Wrong Eiis response: '.$result);
		}
	}

	private function applyData(array $data, array $config){
		$notCreatedCount = 0;
		foreach ($data as $value){
			$obj = $this->getEm()->getRepository($config['class'])->{$config['find_one_method']}($value);
			if(!$obj){
				if($config['create_object_supported']){
					$obj = new $config['class']();
					$this->getEm()->persist($obj);
					if(method_exists($obj,'assignContainer')){
						$obj->assignContainer($this->getContainer());
					}
				}else{
					$notCreatedCount++;
					continue;
				}
			}
			$logs = $obj->{$config['setter']}($value);
			$errors = $this->getContainer()->get('validator')->validate($obj);
			if($errors->count() > 0){
				$message = [];
				/** @var ConstraintViolationInterface $error */
				foreach ($errors as $error){
					$message[] = $error->getPropertyPath().' '.$error->getMessage();
					$this->getLogger()->warning('CREATE '.$config['class'].'#'.$obj->getEiisid().' '.$error->getPropertyPath().' '.$error->getMessage());
				}
				$this->getEm()->detach($obj);
				$this->addLogHistory($obj->getEiisId(), $config['remote_code'], 'warning', implode('; ', $message));
				unset($obj);
				continue;
			}

			foreach ($logs as $log){
				foreach ($log as $key=>$item){
			        	$log[$key] = $item instanceof \DateTime ? $item->format('d.m.Y H:i:s'): $item;
                		}
				$this->addLogHistory($obj->getEiisId(),$config['remote_code'],'info',$log[3].': "'.$log[2].'" -> "'.$log[1].'"');
			}
		}
		if($notCreatedCount > 0){
			$this->getLogger()->warning('Not Created Count: '.$notCreatedCount);
		}
	}

	private function object2array($object){
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

	private function prepareResult(string $xml){
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

	public function clearOldData(){
		$date = new \DateTime('-1 day');
		$this->getQb()
			->delete(EiisUpdateNotification::class,'t')
			->where('t.dateCreated < :date')
			->setParameter('date', $date)
			->getQuery()
			->execute();

		$this->getQb()
			->delete(EiisUpdateNotification::class,'t')
			->where('t.dateCreated < :date')
			->setParameter('date', $date)
			->getQuery()
			->execute();

		$this->getQb()
			->delete(EiisSession::class,'t')
			->where('t.dateCreated < :date')
			->setParameter('date', $date)
			->getQuery()
			->execute();
	}

	public function guidv4(){
		return $this->getContainer()->get('doctrine')->getConnection()->fetchColumn('select uuid()');
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

	private function addLogHistory($eiisid, $remoteCode, $type, $message){
		$log = $this->getEm()->getRepository(EiisLog::class)->findOneBy(['eiisId'=>$eiisid,'systemObjectCode'=>$remoteCode]);
		if(!$log){
			$log = (new EiisLog())->setSystemObjectCode($remoteCode)->setEiisId($eiisid);
			$this->getEm()->persist($log);
		}
		$array = $log->getLoghistory();
		$array[$this->date->format('c')][] = ['type'=>$type,'message'=>$message];
		$log->setLoghistory($array);
		$this->getEm()->flush($log);
	}
}
