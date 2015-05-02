<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.10.14 - 16:30
 */

namespace Prooph\Proophessor\ServiceBus;

use Prooph\Common\ServiceLocator\ZF2\Zf2ServiceManagerProxy;
use Prooph\ServiceBus\ServiceLocator\ServiceLocatorProxy;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class ServiceManagerProxyFactory
 *
 * @package Prooph\Proophessor\ServiceBus
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class ServiceLocatorProxyFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new ServiceLocatorProxy(Zf2ServiceManagerProxy::proxy($serviceLocator));
    }
}
 