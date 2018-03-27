<?php

namespace AppBundle\Output;


use AppBundle\Model\Brewery;
use Symfony\Component\Console\Output\OutputInterface;

class BreweryOutputManager
{
    /** @var OutputInterface */
    private $output;

    /**
     * BreweryOutputManager constructor.
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param Brewery[] $visitedBreweries
     */
    public function outputBreweriesRoutes(array $visitedBreweries)
    {
        $totalDistance = 0;
        $this->output->writeln(sprintf("Found %s breweries: \n", count($visitedBreweries) - 2));
        foreach ($visitedBreweries as $brewery) {
            $this->output->writeln(
                sprintf(
                    "-> %s %s: %s, %s distance %skm",
                    $brewery->getId() ? "[{$brewery->getId()}]" : '',
                    $brewery->getName(),
                    $brewery->getLatitude(),
                    $brewery->getLongitude(),
                    $brewery->getDistance()
                )
            );
            $totalDistance += $brewery->getDistance();
        }
        $this->output->writeln(sprintf("\n Total distance traveled: %s \n", $totalDistance));
    }

    /**
     * @param array $beersList
     */
    public function outputBeersList(array $beersList)
    {
        $this->output->writeln(sprintf("Collected %s beer types:", count($beersList)));
        foreach ($beersList as $beer) {
            $this->output->writeln(sprintf("-> %s", $beer));
        }
    }
}