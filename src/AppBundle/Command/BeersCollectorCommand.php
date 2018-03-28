<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BeersCollectorCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('app:beers-collector:collect')
            ->addArgument('latitude', InputArgument::REQUIRED, 'Latitude')
            ->addArgument('longitude', InputArgument::REQUIRED, 'Longitude')
            ->setDescription('Calculates best route to travel between breweries for finding most unique beers.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        $service = $this->getContainer()->get('app.service.brewery');

        $longitude = (float) $input->getArgument('longitude');
        $latitude = (float)  $input->getArgument('latitude');
        $brewery = $service->buildBreweryObject($longitude, $latitude);
        $service->process($brewery, $output);

        $end = microtime(true);
        $output->writeln($end - $start);
        $output->writeln('Done');
    }


}