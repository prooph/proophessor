<?php
/*
 * This file is part of prooph/proophessor.
 * (c) 2014-2015 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Date: 5/9/15 - 6:23 PM
 */

namespace Prooph\Proophessor\EventStore;

/**
 * Interface AutoCommitCommand
 *
 * This is a marker interface for commands which should not be handled by the transaction manager.
 * The transaction manager will neither begin a new transaction nor commit it.
 *
 * @package Prooph\Proophessor\EventStore
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 */
interface AutoCommitCommand 
{
} 