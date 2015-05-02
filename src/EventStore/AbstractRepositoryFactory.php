<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014 - 2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 05.09.14 - 21:23
 */

namespace Prooph\Proophessor\EventStore;

use Prooph\EventStore\Aggregate\AggregateRepository;
use Prooph\EventStore\Aggregate\AggregateType;
use Prooph\EventStore\Configuration\Exception\ConfigurationException;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractRepositoryFactory
 *
 * @package Prooph\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class AbstractRepositoryFactory implements AbstractFactoryInterface
{
    /**
     * @var array
     */
    protected $repositoryMap;

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (is_null($this->repositoryMap)) {
            if (! $serviceLocator->has('config')) {
                $this->repositoryMap = false;
                return false;
            }

            $config = $serviceLocator->get('config');

            if (! isset($config['proophessor']['event_store']['repository_map'])) {
                $this->repositoryMap = false;
                return false;
            }

            $this->repositoryMap = (array)$config['proophessor']['event_store']['repository_map'];
        }

        return isset($this->repositoryMap[$requestedName]);
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @throws \Prooph\EventStore\Configuration\Exception\ConfigurationException
     * @return AggregateRepository
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $repoConfig = $this->repositoryMap[$requestedName];

        if (is_array($repoConfig)) {
            if (! isset($repoConfig['repository_class'])) {
                throw ConfigurationException::configurationError("Missing repository_class for alias " . $requestedName);
            }

            if (! isset($repoConfig['aggregate_type'])) {
                throw ConfigurationException::configurationError("Missing aggregate_type for alias " . $requestedName);
            }

            $repoClass = $repoConfig['repository_class'];
            $repoAggregateTranslator = isset($repoConfig['aggregate_translator'])? $repoConfig['aggregate_translator'] : "proophessor.event_store.default_aggregate_translator";
            $repoStreamStrategy = isset($repoConfig['stream_strategy'])? $repoConfig['stream_strategy'] : "proophessor.event_store.default_stream_strategy";
            $repoAggregateType = $repoConfig['aggregate_type'];
        } else {
            throw ConfigurationException::configurationError("Wrong type provided for repository map of alias " . $requestedName);
        }

        if (! class_exists($repoClass)) {
            throw ConfigurationException::configurationError(sprintf(
                "Repository alias %s points to a non existing class %s",
                $requestedName,
                $repoClass
            ));
        }

        return new $repoClass(
            $serviceLocator->get('proophessor.event_store'),
            $serviceLocator->get($repoAggregateTranslator),
            $serviceLocator->get($repoStreamStrategy),
            AggregateType::fromString($repoAggregateType)
        );
    }
}
 