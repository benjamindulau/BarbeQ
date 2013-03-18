<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ;

use BarbeQ\Adapter\AdapterInterface;
use BarbeQ\Consumer\ConsumerInterface;
use BarbeQ\Event\ConsumeEvent;
use BarbeQ\Exception\ConsumerIndigestionException;
use BarbeQ\Iterator\MessageIteratorInterface;
use BarbeQ\Model\MessageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BarbeQ
{
    protected $adapter;
    protected $messageDispatcher;
    protected $dispatcher;
    protected $consumingTag;

    public function __construct(
        AdapterInterface $adapter,
        EventDispatcherInterface $messageDispatcher,
        EventDispatcherInterface $dispatcher
    )
    {
        $this->adapter = $adapter;
        $this->messageDispatcher = $messageDispatcher;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * Publish a message
     *
     * @param string           $queue Target queue to push message in
     * @param MessageInterface $message Message content
     */
    public function publish($queue, MessageInterface $message)
    {
        $message->setQueue($queue);
        $this->getAdapter()->publish($queue, $message);
    }

    /**
     * Publish a message
     *
     * @param string           $queue Target queue to push message in
     * @param MessageInterface $message Message content
     */
    public function cook($queue, MessageInterface $message)
    {
        $this->publish($queue, $message);
    }

    /**
     * @return MessageIteratorInterface
     */
    public function getMessages($queue = null)
    {
        return $this->adapter->getMessages($queue);
    }

    /**
     * Dispatches a Message to all interested consumers
     *
     * @param  MessageInterface $message
     *
     * @throws ConsumerIndigestionException
     */
    public function consume(MessageInterface $message)
    {
        $consumeEvent = new ConsumeEvent($message);

        try {
            $this->dispatcher->dispatch(BarbeQEvents::PRE_CONSUME, $consumeEvent);

            $message->start();

            $this->messageDispatcher->dispatch($message->getQueue(), $consumeEvent);
            $this->adapter->onSuccess($message);

            $message->complete();
        } catch(ConsumerIndigestionException $e) {
            $this->adapter->onError($message);

            $message->completeWithError();

            $this->dispatcher->dispatch(BarbeQEvents::POST_CONSUME, $consumeEvent);

            // TODO
            throw new ConsumerIndigestionException("Error while consuming a message", 0, $e);
        }
    }

    /**
     * Dispatches a Message to all interested consumers
     *
     * @param  MessageInterface $message
     */
    public function eat(MessageInterface $message)
    {
        $this->consume($message);
    }

    /**
     * Adds a consumer for messages from the given queue name
     *
     * @param string            $queue
     * @param ConsumerInterface $consumer
     * @param int               $priority
     *
     * @return void
     */
    public function addConsumer($queue, ConsumerInterface $consumer, $priority = 0)
    {
        $this->messageDispatcher->addListener($queue, array($consumer, 'consume'), $priority);
    }

    /**
     * Stops consuming
     */
    public function stopConsuming()
    {
        $this->getAdapter()->stopConsuming();
    }
}