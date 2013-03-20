<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Adapter;

/**
 * RuntimeAdapter, useful for tests and/or dev environment
 *
 * @author Benjamin Dulau <benjamin.dulau@gmail.com>
 */
use BarbeQ\BarbeQ;
use BarbeQ\Model\MessageInterface;

class RuntimeAdapter implements AdapterInterface
{
    /**
     * DISCLAIMER:
     *
     * This adapter being designed for test purpose (dev environment),
     * We need to consume messages right after they are published.
     * Consuming logic being handled by BarbeQ and not the adapter, we need this lovely hack ;-)
     * Pragmatic and proud!
     *
     * @var BarbeQ
     */
    protected $barbeQ;

    /**
     * {@inheritDoc}
     */
    public function publish($queue, MessageInterface $message)
    {
        $this->barbeQ->consumeOne($message);
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages($queue = null)
    {
        return new \EmptyIterator();
    }

    /**
     * {@inheritDoc}
     */
    public function onSuccess(MessageInterface $message)
    {
        // Nothing to do
    }

    /**
     * {@inheritDoc}
     */
    public function onError(MessageInterface $message)
    {
        // Nothing to do
    }

    /**
     * {@inheritDoc}
     */
    public function stopConsuming()
    {
        // Nothing to do
    }


    /**
     * Sets BarbeQ reference
     *
     * @param BarbeQ $barbeQ
     */
    public function setBarbeQ(BarbeQ $barbeQ)
    {
        $this->barbeQ = $barbeQ;
    }
}