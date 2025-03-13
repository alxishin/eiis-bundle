<?php

namespace Corp\EiisBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * @deprecated
 */
#[ORM\Table(name: 'eiis_log')]
#[ORM\Entity(repositoryClass: 'Corp\EiisBundle\Repository\EiisLogRepository')]
class EiisLog
{

    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;


    /**
     * @var string
     */
    #[ORM\Column(name: 'systemObjectCode', type: 'string', length: 255)]
    private $systemObjectCode;


    /**
     * @var array
     */
    #[ORM\Column(name: 'loghistory', type: 'json')]
	private $loghistory;



    /**
     * @var string
     */
    #[ORM\Column(name: 'EiisId', type: 'string', length: 36, nullable: true)]
	private $eiisId;



    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'dateCreated', type: 'datetime')]
	private $dateCreated;


    /**@var int */
    #[ORM\Column(name: 'type', type: 'integer', nullable: true)]
    private $type;

    const TYPE_NEW = 1;

    public static $typeString = [
        self::TYPE_NEW => 'new'
    ];

	/**
	 * Set loghistory
	 *
	 * @param array $loghistory
	 *
	 * @return $this
	 */
	public function setLoghistory($loghistory)
	{
		$this->loghistory = $loghistory;

		return $this;
	}

	/**
	 * Get loghistory
	 *
	 * @return array
	 */
	public function getLoghistory()
	{
		return $this->loghistory;
	}

    /**
     * EiisLog constructor.
     * @param \DateTime $dateCreated
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set systemObjectCode
     *
     * @param string $systemObjectCode
     *
     * @return EiisLog
     */
    public function setSystemObjectCode($systemObjectCode)
    {
        $this->systemObjectCode = $systemObjectCode;

        return $this;
    }

    /**
     * Get systemObjectCode
     *
     * @return string
     */
    public function getSystemObjectCode()
    {
        return $this->systemObjectCode;
    }

    /**
     * Set eiisId
     *
     * @param string $eiisId
     *
     * @return EiisLog
     */
    public function setEiisId($eiisId)
    {
        $this->eiisId = $eiisId;

        return $this;
    }

    /**
     * Get eiisId
     *
     * @return string
     */
    public function getEiisId()
    {
        return $this->eiisId;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return EiisLog
     */
    public function setDateCreated($dateCreated)
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * Get dateCreated
     *
     * @return \DateTime
     */
    public function getDateCreated()
    {
        return $this->dateCreated;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}
