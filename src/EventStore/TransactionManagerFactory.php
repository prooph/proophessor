<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/2/15 - 8:30 PM
 */
namespace Prooph\Proophessor\EventStore;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class TransactionManagerFactory
 *
 * @package Prooph\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
final class TransactionManagerFactory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return new TransactionManager($serviceLocator->get('proophessor.event_store'));
    }
}