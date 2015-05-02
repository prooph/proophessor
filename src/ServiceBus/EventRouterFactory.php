<?php
/*
 * This file is part of the prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 30.10.14 - 16:28
 */

namespace Prooph\Proophessor\ServiceBus;

/**
 * Class EventRouterFactory
 *
 * @package Prooph\Proophessor\ServiceBus
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
class EventRouterFactory extends AbstractRouterFactory
{

    /**
     * @return string
     */
    protected function getRouterClass()
    {
        return 'Prooph\ServiceBus\Router\EventRouter';
    }

    /**
     * Return config key used within the prooph.psb config namespace to define utils list for the bus.
     *
     * @return string
     */
    protected function getRoutingMapConfigKey()
    {
        return 'event_router_map';
    }
}
 