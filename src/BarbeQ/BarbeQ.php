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
use BarbeQ\Exception\BarbeQException;
use BarbeQ\Model\MessageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class BarbeQ
{
    protected $adapter;
    protected $messageDispatcher;
    protected $dispatcher;

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

    public function createMessage(array $body, $priority)
    {
        return $this->adapter->createMessage($body, (int) $priority);
    }

    public function publish($queue, MessageInterface $message)
    {
        $this->getAdapter()->publish($queue, $message);
    }

    public function createAndPublish($queue, array $messageBody, $messagePriority)
    {
        $message = $this->createMessage($messageBody, $messagePriority);
        $this->publish($queue, $message);
    }

    public function getMessageIterator()
    {
        return $this->adapter->getMessageIterator();
    }

    public function consume(MessageInterface $message)
    {
        $consumeEvent = new ConsumeEvent($message);

        try {
            $this->dispatcher->dispatch(BarbeQEvents::PRE_CONSUME, $consumeEvent);

            $message->start();

            $this->messageDispatcher->dispatch($message->getQueue(), $consumeEvent);
            $this->adapter->onSuccess($message);

            $message->complete();
        } catch(BarbeQException $e) {
            $this->adapter->onError($message);

            $message->completeWithError();

            $this->dispatcher->dispatch(BarbeQEvents::POST_CONSUME, $consumeEvent);

            // TODO
            throw new BarbeQException("Error while consuming a message", 0, $e);
        }
    }

    /**
     * Adds a consumer for messages from the given queue name
     *
     * @param string            $queue
     * @param ConsumerInterface $consumer
     */
    public function addConsumer($queue, ConsumerInterface $consumer)
    {
        $this->messageDispatcher->addListener($queue, array($consumer, 'consume'));
    }
}