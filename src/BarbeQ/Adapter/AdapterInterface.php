<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Adapter;

use BarbeQ\Iterator\MessageIteratorInterface;
use BarbeQ\Model\MessageInterface;

interface AdapterInterface
{
    /**
     * Publish (push) a message to the queue
     *
     * @param string $queue
     * @param MessageInterface $message
     *
     * @return mixed
     */
    public function publish($queue, MessageInterface $message);

    /**
     * Returns messages for the given queue
     *
     * @param string $queue
     *
     * @return MessageIteratorInterface
     */
    public function getMessages($queue = null);

    /**
     * Handles success after a message has been successfully consumed
     *
     * @param MessageInterface $message
     *
     * @return void
     */
    public function onSuccess(MessageInterface $message);


    /**
     * Handles error on message consuming
     *
     * @param MessageInterface $message
     *
     * @return void
     */
    public function onError(MessageInterface $message);

    /**
     * Stops consuming
     * Used for instance when using a message limit
     *
     * @return mixed
     */
    public function stopConsuming();
}