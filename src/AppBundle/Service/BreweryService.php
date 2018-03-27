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
        $totalDistance = 0;
        $firstBrewery = $startingBrewery;
        $visitedBreweries = [$startingBrewery];

        $breweries = $this->getClosestBreweries($startingBrewery);

        do {
            $filteredBrewery = $this->getHighestScoreBrewery($startingBrewery, $breweries);
            $distanceTillFirst = $this->getDistanceTillFirstBrewery($startingBrewery, $firstBrewery);
            if ($totalDistance + $distanceTillFirst < self::FULL_TRAVEL_DISTANCE) {
                $totalDistance += $filteredBrewery->getDistance();
                $startingBrewery = $filteredBrewery;
                $visitedBreweries[] = $filteredBrewery;
            } else {
                $firstBrewery->setDistance($distanceTillFirst);
                $visitedBreweries[] = $firstBrewery;

                break;
            }
        } while ($totalDistance < self::FULL_TRAVEL_DISTANCE);

        return $visitedBreweries;
    }
    
    /**
     * @param Brewery $startingBrewery
     * @param Brewery[] $breweries
     * @return Brewery
     */
    private function getHighestScoreBrewery(Brewery $startingBrewery, array $breweries): Brewery
    {
        $score = self::HALF_TRAVEL_DISTANCE;
        $bestMatch = null;

        /** @var Brewery $brewery */
        foreach ($breweries as $key => $brewery) {
            if (in_array($brewery->getId(), $this->visitedBreweriesIds)) {
                continue;
            }

            if (empty($brewery->getDistance())) {
                $distance = $this->calculateDistance(
                    $startingBrewery->getLongitude(),
                    $startingBrewery->getLatitude(),
                    $brewery->getLongitude(),
                    $brewery->getLatitude()
                );
            } else {
                $distance = $brewery->getDistance();
            }

            $newBeersCount = count(array_diff($brewery->getBeers(), $this->uniqueBeers));
            if ($score > $distance / $newBeersCount) {
                $score = $distance / $newBeersCount;
                $brewery->setDistance($distance);
                $bestMatch = $brewery;
            }
        }
        $this->visitedBreweriesIds[] = $bestMatch->getId();
        $this->uniqueBeers = array_merge($this->uniqueBeers, $bestMatch->getBeers());

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
                $breweryFromDb->setDistance($distance);
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
     * @return int
     */
    private function getDistanceTillFirstBrewery(Brewery $breweryFrom, Brewery $firstBrewery)
    {
        return $this->calculateDistance(
            $breweryFrom->getLongitude(),
            $breweryFrom->getLatitude(),
            $firstBrewery->getLongitude(),
            $firstBrewery->getLatitude()
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
        return array_merge(...$uniqueBeers);
    }

}