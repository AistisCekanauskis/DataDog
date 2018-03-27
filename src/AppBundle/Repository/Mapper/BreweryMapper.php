<?php

namespace AppBundle\Repository\Mapper;

use AppBundle\Model\Brewery;

class BreweryMapper
{
    /**
     * @param array $row
     * @return Brewery
     */
    public static function mapRowToObject(array $row): Brewery
    {
        $brewery = new Brewery();
        $brewery->setId($row['id']);
        $brewery->setName($row['name']);
        $brewery->setLatitude($row['latitude']);
        $brewery->setLongitude($row['longitude']);
        $brewery->setBeers(explode(',', $row['beers']));

        return $brewery;
    }
}