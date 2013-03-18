<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Model;

interface MessageInterface
{
    const STATE_PENDING = 0;
    const STATE_PROCESSING = 1;
    const STATE_ABORTED = 3;
    const STATE_COMPLETE = 4;
    const STATE_ERROR = -1;

    /**
     * Set the message body
     *
     * @param array $body
     *
     * @return void
     */
    public function setBody(array $body);

    /**
     * Returns message body
     *
     * @return array
     */
    public function getBody();

    /**
     * Sets metadata
     *
     * @param array $metadata
     *
     * @return void
     */
    public function setMetadata(array $metadata);

    /**
     * Returns all metadata
     *
     * @return array
     */
    public function getMetadata();

    /**
     * Returns a metadata value
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getMetadataValue($name, $default = null);

    /**
     * Adds a metadata value
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return void
     */
    public function addMetadata($name, $value);

    /**
     * Returns message's current state
     *
     * @return int
     */
    public function getState();

    /**
     * Sets message's state
     *
     * @param int $state
     *
     * @return void
     */
    public function setState($state);

    /**
     * Starts processing the message
     *
     * @return void
     */
    public function start();

    /**
     * Returns message's start date
     *
     * @return \DateTime
     */
    public function getStartedAt();

    /**
     * Completes the message
     *
     * @return void
     */
    public function complete();

    /**
     * Completes the message but with Errors
     *
     * @return void
     */
    public function completeWithError();

    /**
     * Returns message's start date
     *
     * @return \DateTime
     */
    public function getCompletedAt();

    /**
     * Sets the message priority
     *
     * @param int $priority
     */
    public function setPriority($priority);

    /**
     * Returns the message priority
     *
     * @return float
     */
    public function getPriority();

    /**
     * Returns execution time
     *
     * @return float
     */
    public function getTime();

    /**
     * Returns consumed memory
     *
     * @return int
     */
    public function getMemory();

    /**
     * Sets message queue
     *
     * @param string $queue
     */
    public function setQueue($queue);

    /**
     * Returns the queue name the message is appended into
     *
     * @return string
     */
    public function getQueue();

    /**
     * Serializes Message metadata + body to Json format
     *
     * @return string
     */
    public function toJson();

    /**
     * Unserializes data from Json and returns a new Message
     *
     * @param string $json
     * @return MessageInterface
     */
    public static function fromJson($json);
}