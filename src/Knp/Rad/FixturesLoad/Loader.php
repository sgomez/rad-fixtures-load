<?php

namespace Knp\Rad\FixturesLoad;

use Doctrine\Common\Persistence\ManagerRegistry;
use Knp\Rad\FixturesLoad\FixturesFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Loader
{
    /**
     * @var Nelmio\Alice\Fixtures $fixtures
     */
    private $fixtures;

    /**
     * @var ManagerRegistry $doctrine
     */
    private $doctrine;

    /**
     * @var EventDispatcherInterface $dispatcher
     */
    private $dispatcher;

    /**
     * @param ManagerRegistry $doctrine
     * @param FixturesFactory $factory
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ManagerRegistry $doctrine, FixturesFactory $factory, EventDispatcherInterface $dispatcher)
    {
        $this->doctrine   = $doctrine;
        $this->factory    = $factory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Bundle $bundle
     * @param string[] $filters
     * @param string $connection
     * @param string|null $locale
     *
     * @return void
     */
    public function loadFixtures(Bundle $bundle, array $filters, $connection, $locale = null)
    {
        $files = (new Finder)
            ->files()
            ->in(sprintf('%s/Resources/fixtures/orm', $bundle->getPath()))
            ->sortByName()
        ;

        foreach ($filters as $filter) {
            $files->name($filter);
        }

        foreach ($files as $file)
        {
            $event = new Event($bundle, $file);
            $this->dispatcher->dispatch(Events::PRE_LOAD, $event);
            $event->setObjects($this->getFixtures($connection, $locale)->loadFiles($file));
            $this->dispatcher->dispatch(Events::POST_LOAD, $event);
        }
    }

    /**
     * @param string $name
     *
     * @return Doctrine\Common\Persistence\ObjectManager
     */
    private function getObjectManager($name)
    {
        if (null === $manager = $this->doctrine->getManager($name)) {
            throw new \Exception(sprintf('Can\'t find doctrine manager named "%s"'), $name);
        }

        return $manager;
    }

    /**
     * @param string $connection
     * @param string|null $locale
     *
     * @return Nelmio\Alice\Fixtures
     */
    private function getFixtures($connection, $locale = null)
    {
        if (null !== $this->fixtures) {
            return $this->fixtures;
        }

        $om = $this->getObjectManager($connection);

        return $this->fixtures = $this->factory->create($om, $locale);
    }
}