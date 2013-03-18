<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Adapter;

use BarbeQ\Model\MessageInterface;

interface AdapterInterface
{
    /**
     * @param array $body
     * @param int   $priority
     *
     * @return MessageInterface
     */
    public function createMessage(array $body, $priority);

    /**
     * Publish (push) a message to the queue
     *
     * @param string $queue
     * @param MessageInterface $message
     *
     * @return mixed
     */
    public function publish($queue, MessageInterface $message);

    public function getMessageIterator();

    public function onSuccess(MessageInterface $message);

    public function onError(MessageInterface $message);


}