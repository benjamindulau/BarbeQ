<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Consumer;

use BarbeQ\Event\ConsumeEvent;

interface ConsumerInterface
{
    public function consume(ConsumeEvent $event);
}