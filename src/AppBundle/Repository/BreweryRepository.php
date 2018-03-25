<?php

namespace AppBundle\Repository;

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
}