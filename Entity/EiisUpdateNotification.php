<?php

namespace Corp\EiisBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Table(name: 'EiisUpdateNotification')]
#[ORM\UniqueConstraint(name: 'uniq_index1', columns: ['systemObjectCode', 'signalFrom'])]
#[ORM\Entity]
class EiisUpdateNotification
{


    /**
     * @var int
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private $id;



    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'dateCreated', type: 'datetime')]
    private $dateCreated;



    /**
     * @var string
     */
    #[ORM\Column(name: 'systemObjectCode', type: 'string', length: 255)]
    private $systemObjectCode;


    /**
     * @var int
     */
    #[ORM\Column(name: 'signalFrom', type: 'integer')]
    private $signalFrom;

    /**
     * EiisUpdateNotification constructor.
     */
    public function __construct()
    {
        $this->dateCreated = new \DateTime();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return EiisUpdateNotification
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
     * Set systemObjectCode
     *
     * @param string $systemObjectCode
     *
     * @return EiisUpdateNotification
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
     * Set signalFrom
     *
     * @param integer $signalFrom
     *
     * @return EiisUpdateNotification
     */
    public function setSignalFrom($signalFrom)
    {
        $this->signalFrom = $signalFrom;

        return $this;
    }

    /**
     * Get signalFrom
     *
     * @return integer
     */
    public function getSignalFrom()
    {
        return $this->signalFrom;
    }
}
