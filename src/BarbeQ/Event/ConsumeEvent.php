<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Event;

use BarbeQ\Model\MessageInterface;
use Symfony\Component\EventDispatcher\Event;

class ConsumeEvent extends Event
{
    protected $message;

    /**
     * @param MessageInterface $message
     */
    public function __construct(MessageInterface $message)
    {
        $this->message = $message;
    }

    /**
     * @return MessageInterface
     */
    public function getMessage()
    {
        return $this->message;
    }
}
