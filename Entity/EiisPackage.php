<?php

namespace Corp\EiisBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * EiisPackage
 */
#[ORM\Table(name: 'EiisPackage')]
#[ORM\Entity]
class EiisPackage
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
     * @var string
     */
    #[ORM\Column(name: 'uuid', type: 'string', length: 36, unique: true)]
    private $uuid;


    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'dateCreated', type: 'datetime')]
    private $dateCreated;



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
     * Set systemObjectCode
     *
     * @param string $systemObjectCode
     *
     * @return EiisPackage
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
     * Set uuid
     *
     * @param string $uuid
     *
     * @return EiisPackage
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set dateCreated
     *
     * @param \DateTime $dateCreated
     *
     * @return EiisPackage
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
}
