<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.10.14 - 12:57
 */

namespace Prooph\Proophessor\ServiceBus;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class AbstractBusFactory
 *
 * @package Prooph\Proophessor\ServiceBus
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
abstract class AbstractBusFactory implements FactoryInterface
{
    /**
     * @return string
     */
    abstract protected function getBusClass();

    /**
     * Return config key used within the prooph.psb config namespace to define utils list for the bus.
     *
     * @return string
     */
    abstract protected function getConfigKey();

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @throws \LogicException
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('config');

        if (!is_array($config)) {
            throw new \LogicException("Missing application config");
        }

        if (!isset($config['proophessor'])) {
            throw new \LogicException("Missing proophessor config");
        }

        if (!isset($config['proophessor'][$this->getConfigKey()])) {
            throw new \LogicException(sprintf(
                "Missing %s config key in proophessor config",
                $this->getConfigKey()
            ));
        }

        $busUtilsList = $config['proophessor'][$this->getConfigKey()];

        if (!is_array($busUtilsList)) {
            throw new \LogicException(sprintf(
                "proophessor.%s config needs to be an array",
                $this->getConfigKey()
            ));
        }

        $busClass = $this->getBusClass();

        $bus = new $busClass();
        foreach($busUtilsList as $util) {
            $bus->utilize($serviceLocator->get($util));
        }

        return $bus;
    }
}
 