<?php

namespace AppBundle\Model;

class Brewery
{
    /** @var int */
    private $id;

    /** @var string */
    private $name;

    /** @var float */
    private $latitude;

    /** @var float */
    private $longitude;

    /** @var array */
    private $beers = [];

    /** @var int */
    private $distance = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     */
    public function setLatitude(float $latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     */
    public function setLongitude(float $longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * @return array
     */
    public function getBeers(): array
    {
        return $this->beers;
    }

    /**
     * @param array $beers
     */
    public function setBeers(array $beers)
    {
        $this->beers = $beers;
    }

    /**
     * @return int
     */
    public function getDistance(): int
    {
        return $this->distance;
    }

    /**
     * @param int $distance
     */
    public function setDistance(int $distance)
    {
        $this->distance = $distance;
    }
}