<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 4/29/15 - 10:05 PM
 */
namespace Prooph\Proophessor\EventStore;

use Prooph\Common\ServiceLocator\ZF2\Zf2ServiceManagerProxy;
use Prooph\EventStore\Configuration\Configuration;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Feature\FeatureManager;
use Prooph\EventStore\Feature\ZF2FeatureManager;
use Zend\ServiceManager\Config;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class EventStoreFactory
 *
 * @package Prooph\Proophessor\EventStore\Factory
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class EventStoreFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @throws \RuntimeException
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get("configuration");

        if (! isset($config['proophessor'])) {
            throw new \RuntimeException("Missing proophessor config key in application config");
        }

        if (! isset($config['proophessor']['event_store'])) {
            throw new \RuntimeException("Missing key event_store in proophessor configuration");
        }

        $config = $config['proophessor']['event_store'];

        if (! isset($config['adapter'])) {
            throw new \RuntimeException("Missing adapter configuration in proophessor event_store configuration");
        }

        $adapterConfig = $config['adapter'];

        if (! isset($adapterConfig['type'])) {
            throw new \RuntimeException("Missing adapter type configuration in proophessor event_store configuration");
        }

        $adapterType    = $adapterConfig['type'];
        $adapterOptions = isset($adapterConfig['options'])? $adapterConfig['options'] : [];

        //Check if we have to use the application wide database connection
        if ($adapterType == 'Prooph\EventStore\Adapter\Doctrine\DoctrineEventStoreAdapter'
            && !isset($adapterOptions['connection'])
            && isset($adapterOptions['doctrine_connection_alias'])) {
            $config['adapter']['options']['connection'] = $serviceLocator->get('doctrine.connection.' . $adapterOptions['doctrine_connection_alias']);
        } else if ( $adapterType == 'Prooph\EventStore\Adapter\Zf2\Zf2EventStoreAdapter'
            && isset($adapterOptions['zend_db_adapter'])
            && is_string($adapterOptions['zend_db_adapter'])) {
            $config['adapter']['options']['zend_db_adapter'] = $serviceLocator->get($adapterOptions['zend_db_adapter']);
        }

        $featureManagerConfig = null;

        if (isset($config['feature_manager'])) {
            $featureManagerConfig = new Config($config['feature_manager']);
            unset($config['feature_manager']);
        }

        $esConfiguration = new Configuration($config);

        $featureManager = new ZF2FeatureManager($featureManagerConfig);

        $featureManager->setServiceLocator($serviceLocator);

        $esConfiguration->setFeatureManager(Zf2ServiceManagerProxy::proxy($featureManager));

        return new EventStore($esConfiguration);
    }
}