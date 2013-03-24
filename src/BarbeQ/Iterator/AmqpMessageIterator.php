<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Iterator;

use BarbeQ\Model\Message;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;

class AmqpMessageIterator implements MessageIteratorInterface
{
    protected $channel;
    protected $message;
    protected $AMQMessage;
    protected $queue;
    protected $counter;
    protected $consumingTag;

    /**
     * @param AMQPChannel $channel
     * @param string      $queue
     * @param string      $consumingTag
     */
    public function __construct(AMQPChannel $channel, $queue, $consumingTag)
    {
        $this->consumingTag = $consumingTag;
        $this->channel = $channel;
        $this->queue   = $queue;
        $this->counter = 0;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->message;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->wait();
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        $this->counter;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return count($this->channel->callbacks);
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->channel->basic_consume(
            $this->queue,
            $this->consumingTag,
            false,
            false,
            false,
            false,
            array($this, 'receiveMessage')
        );

        $this->wait();

        return $this->message;
    }

    protected function wait()
    {
        while ($this->valid()) {
            $this->channel->wait();

            break;
        }
    }

    /**
     * @param  \PhpAmqpLib\Message\AMQPMessage $AMQMessage
     * @return void
     */
    public function receiveMessage(AMQPMessage $AMQMessage)
    {
        $this->AMQMessage = $AMQMessage;

        $message = Message::fromJson($this->AMQMessage->body);
        $message->addMetadata('AmqpMessage', $AMQMessage);
        $this->counter++;

        $this->message = $message;
    }
}
