<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Model;

class Message implements MessageInterface
{
    protected $state;
    protected $body;
    protected $startedAt;
    protected $completedAt;
    protected $priority;
    protected $time;
    protected $memory;
    protected $queue;
    protected $startTime;
    protected $startMemory;
    protected $metadata;

    public function __construct(array $body = array(), $priority = 0)
    {
        $this->setBody($body);
        $this->setPriority($priority);

        $this->setState(self::STATE_PENDING);
        $this->time = 0;
        $this->memory = 0;
        $this->metadata = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setBody(array $body)
    {
        $this->body = $body;
    }

    /**
     * {@inheritdoc}
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * {@inheritdoc}
     */
    public function setMetadata(array $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function addMetadata($name, $value)
    {
        $this->metadata[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataValue($name, $default = null)
    {
        return isset($this->metadata[$name]) ? $this->metadata[$name] : $default;
    }


    /**
     * {@inheritdoc}
     */
    public function setQueue($queue)
    {
        $this->queue = $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * {@inheritdoc}
     */
    public function setState($state)
    {
        if (!in_array($state, array(
            self::STATE_PENDING,
            self::STATE_ABORTED,
            self::STATE_COMPLETE,
            self::STATE_ERROR,
            self::STATE_PROCESSING,
        ))) {
            throw new \InvalidArgumentException(sprintf('State "%s" is not a valid state for a message.'), (string) $state);
        }

        $this->state = $state;
    }

    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = (int) $priority;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        $this->startMemory = memory_get_usage(true);
        $this->startTime = microtime(true);

        $this->startedAt = new \DateTime();
        $this->setState(self::STATE_PROCESSING);
    }

    /**
     * {@inheritdoc}
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        $this->completedAt = new \DateTime();
        $this->setState(self::STATE_COMPLETE);

        $this->time = microtime(true) - $this->startTime;

        $memory = memory_get_usage(true) - $this->startMemory;
        if ($memory < 1024) {
            $memory .= 'b';
        } elseif ($memory < 1048576) {
            $memory = round($memory / 1024, 2) . 'Kb';
        } else {
            $memory = round($memory / 1048576, 2) . 'Mb';
        }
        $this->memory = $memory;
    }

    /**
     * {@inheritdoc}
     */
    public function getCompletedAt()
    {
        return $this->completedAt;
    }

    /**
     * {@inheritdoc}
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * {@inheritdoc}
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * {@inheritdoc}
     */
    public function completeWithError()
    {
        // TODO: Implement completeWithError() method.
    }

    public function toJson()
    {
        return json_encode(array(
            'state' => $this->getState(),
            'priority' => $this->getPriority(),
            'queue' => $this->getQueue(),
            'body' => $this->getBody(),
        ));
    }

    public static function fromJson($json)
    {
        $data = json_decode($json, true);
        $message = new self($data['body'], $data['priority']);
        $message->setState($data['state']);
        $message->setQueue($data['queue']);

        return $message;
    }
}