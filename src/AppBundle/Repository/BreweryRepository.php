<?php

namespace AppBundle\Repository;

use AppBundle\Model\Brewery;
use AppBundle\Repository\Mapper\BreweryMapper;
use Doctrine\DBAL\Connection;

class BreweryRepository
{
    /** @var  Connection */
    protected $connection;

    /**
     * BreweryRepository constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @return Brewery[]
     */
    public function getBreweriesWithBeers(): array
    {
        $query = "
            SELECT b.id, b.name, bgc.latitude, bgc.longitude, GROUP_CONCAT(br.name SEPARATOR ',') AS beers FROM Breweries b 
            JOIN BreweriesGeoCode bgc ON bgc.breweryId = b.id
            JOIN Beers br ON br.breweryId = b.id
            GROUP BY b.id, bgc.latitude, bgc.longitude
        ";
        $data =  $this->connection->fetchAll($query);

        $breweries = [];
        foreach ($data as $row) {
            $breweries[] = BreweryMapper::mapRowToObject($row);
        }

        return $breweries;
    }
}