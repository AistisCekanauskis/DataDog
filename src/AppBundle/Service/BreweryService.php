<?php

namespace AppBundle\Service;

use AppBundle\Model\Brewery;
use AppBundle\Output\BreweryOutputManager;
use AppBundle\Repository\BreweryRepository;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Output\OutputInterface;

class BreweryService
{
    const FULL_TRAVEL_DISTANCE = 2000;
    const HALF_TRAVEL_DISTANCE = 1000;

    /** @var BreweryRepository */
    private $repository;

    /** @var array */
    private $visitedBreweriesIds = [];

    /** @var array */
    private $uniqueBeers = [];

    /** @var Brewery */
    private $firstBrewery;

    /** @var int */
    private $distanceLeft = self::FULL_TRAVEL_DISTANCE;

    /** @var bool */
    private $finished = false;

    /**
     * Service constructor.
     * @param BreweryRepository $repository
     */
    public function __construct(BreweryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Brewery $startingBrewery
     * @param OutputInterface $output
     */
    public function process(Brewery $startingBrewery, OutputInterface $output)
    {
        $visitedBreweries = $this->filterBreweries($startingBrewery);
        $beersList = $this->getUniqueBeers($visitedBreweries);

        $outputManager = new BreweryOutputManager($output);
        $outputManager->outputBreweriesRoutes($visitedBreweries);
        $outputManager->outputBeersList($beersList);
    }

    /**
     * @param float $longitude
     * @param float $latitude
     * @return Brewery
     */
    public function buildBreweryObject(float $longitude, float $latitude): Brewery
    {
        $brewery = new Brewery();
        $brewery->setName('HOME');
        $brewery->setDistance(0);
        $brewery->setLongitude($longitude);
        $brewery->setLatitude($latitude);
        $brewery->setBeers([]);

        return $brewery;
    }


    /**
     * @param Brewery $startingBrewery
     * @return array
     */
    public function filterBreweries(Brewery $startingBrewery)
    {
        $this->firstBrewery = $startingBrewery;
        $visitedBreweries = [$startingBrewery];

        $breweries = $this->getClosestBreweries($startingBrewery);

        do {
            $nextBrewery = $this->getNextBrewery($startingBrewery, $breweries);

            $this->visitedBreweriesIds[] = $nextBrewery->getId();
            $this->uniqueBeers = array_merge($this->uniqueBeers, $nextBrewery->getBeers());
            $this->distanceLeft -= $nextBrewery->getDistance();

            $startingBrewery = $nextBrewery;
            $visitedBreweries[] = $nextBrewery;
        } while ($this->finished === false);

        return $visitedBreweries;
    }
    
    /**
     * Gets next brewery with most unique beers and lowest distance - lowest $score
     * @param Brewery $startingBrewery
     * @param Brewery[] $breweries
     * @return Brewery
     */
    private function getNextBrewery(Brewery $startingBrewery, array $breweries): Brewery
    {
        $score = self::FULL_TRAVEL_DISTANCE;
        $bestMatch = null;

        /** @var Brewery $brewery */
        foreach ($breweries as $key => $nextBrewery) {
            if (in_array($nextBrewery->getId(), $this->visitedBreweriesIds)) {
                continue;
            }

            $distanceTillNext = $this->getDistanceTillNextBrewery($startingBrewery, $nextBrewery);
            $distanceTillFirst = $this->getDistanceTillFirstBrewery($nextBrewery);
            $totalDistance = $distanceTillNext + $distanceTillFirst;

            $uniqueBeersCount = count(array_diff($nextBrewery->getBeers(), $this->uniqueBeers));
            $uniqueBeersCount === 0 && $uniqueBeersCount = 1;

            $newScore = $totalDistance / $uniqueBeersCount;

            if ($score > $newScore && $totalDistance < $this->distanceLeft) {
                $score = $newScore;
                $nextBrewery->setDistance($distanceTillNext);
                $bestMatch = $nextBrewery;
            }
        }

        if ($bestMatch === null) {
            $bestMatch = clone $this->firstBrewery;
            $bestMatch->setDistance($this->getDistanceTillFirstBrewery($startingBrewery));
            $this->finished = true;
        }

        return $bestMatch;
    }

    /**
     * Only take breweries closer than 1000 km
     * @param Brewery $brewery
     * @return array
     */
    private function getClosestBreweries(Brewery $brewery): array
    {
        $filteredBreweries = [];
        $breweries = $this->repository->getBreweriesWithBeers();

        /** @var Brewery $breweryFromDb */
        foreach ($breweries as $breweryFromDb) {
            $distance = $this->calculateDistance(
                $brewery->getLongitude(),
                $brewery->getLatitude(),
                $breweryFromDb->getLongitude(),
                $breweryFromDb->getLatitude()
            );
            if ($distance < self::HALF_TRAVEL_DISTANCE) {
                $filteredBreweries[] = $breweryFromDb;
            }
        }

        if (empty($breweries)) {
            throw new Exception('No near breweries were found.');
        }

        return $filteredBreweries;
    }

    /**
     * @param Brewery $breweryFrom
     * @param Brewery $nextBrewery
     * @return int
     */
    private function getDistanceTillNextBrewery(Brewery $breweryFrom, Brewery $nextBrewery)
    {
        return $this->calculateDistance(
            $breweryFrom->getLongitude(),
            $breweryFrom->getLatitude(),
            $nextBrewery->getLongitude(),
            $nextBrewery->getLatitude()
        );
    }

    /**
     * @param Brewery $breweryFrom
     * @return int
     */
    private function getDistanceTillFirstBrewery(Brewery $breweryFrom)
    {
        return $this->calculateDistance(
            $breweryFrom->getLongitude(),
            $breweryFrom->getLatitude(),
            $this->firstBrewery->getLongitude(),
            $this->firstBrewery->getLatitude()
        );
    }

    /**
     * @param string $lon1
     * @param string $lat1
     * @param string $lon2
     * @param string $lat2
     * @return int
     */
    private function calculateDistance(string $lon1, string $lat1, string $lon2, string $lat2): int
    {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);

        return $dist * 60 * 1.1515 * 1.609344;
    }

    /**
     * @param Brewery[] $visitedBreweries
     * @return array
     */
    private function getUniqueBeers(array $visitedBreweries): array
    {
        $uniqueBeers = [];
        /** @var Brewery $brewery */
        foreach ($visitedBreweries as $brewery) {
            $uniqueBeers[] = $brewery->getBeers();
        }
        $uniqueBeers = array_unique(array_merge(...$uniqueBeers));
        sort($uniqueBeers);

        return $uniqueBeers;
    }

}