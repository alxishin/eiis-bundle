<?php

namespace Corp\EiisBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * EiisPackage
 */
#[ORM\Table(name: 'EiisSession')]
#[ORM\Entity]
class EiisSession
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
    #[ORM\Column(name: 'uuid', type: 'string', length: 36, unique: true)]
    private $uuid;



    /**
     * @var \DateTime
     */
    #[ORM\Column(name: 'dateCreated', type: 'datetime')]
    private $dateCreated;

    /**
     * EiisSession constructor.
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
     * Set uuid
     *
     * @param string $uuid
     *
     * @return EiisSession
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
     * @return EiisSession
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
