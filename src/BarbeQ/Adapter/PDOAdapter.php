<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Adapter;

use BarbeQ\Iterator\PdoMessageIterator;
use BarbeQ\Model\MessageInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PdoAdapter implements AdapterInterface
{
    protected $pdo;
    protected $dbOptions;

    /**
     * Constructor.
     *
     * @param \PDO  $pdo       A \PDO instance
     * @param array $dbOptions An associative array of DB options
     */
    public function __construct(\PDO $pdo, array $dbOptions = array())
    {
        $this->pdo = $pdo;

        $optionResolver = new OptionsResolver();
        $this->setDefaultDbOptions($optionResolver);
        $this->dbOptions = $optionResolver->resolve($dbOptions);
    }

    /**
     * {@inheritDoc}
     */
    public function publish($queue, MessageInterface $message)
    {
        $sql = 'INSERT INTO %s (%s, %s, %s, %s, %s)'
        . ' VALUES (:body, :queue, :state, :priority, :metadata)';

        $sql = sprintf($sql,
            $this->dbOptions['table'],
            $this->dbOptions['body'],
            $this->dbOptions['queue'],
            $this->dbOptions['state'],
            $this->dbOptions['priority'],
            $this->dbOptions['metadata']
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':body', serialize($message->getBody()), \PDO::PARAM_STR);
        $stmt->bindParam(':queue', $message->getQueue(), \PDO::PARAM_STR);
        $stmt->bindParam(':state', $message->getState(), \PDO::PARAM_INT);
        $stmt->bindParam(':priority', $message->getPriority(), \PDO::PARAM_INT);
        $stmt->bindParam(':metadata', serialize($message->getMetadata()), \PDO::PARAM_STR);
        $stmt->execute();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getMessages($queue = null)
    {
        return new PdoMessageIterator($this->pdo, $this->dbOptions, $queue);
    }

    /**
     * {@inheritDoc}
     */
    public function onSuccess(MessageInterface $message)
    {
        $this->saveMessage($message);
    }

    /**
     * {@inheritDoc}
     */
    public function onError(MessageInterface $message)
    {
        $this->saveMessage($message);
    }

    /**
     * {@inheritDoc}
     */
    public function stopConsuming()
    {
        // nothing to do here
    }

    protected function saveMessage(MessageInterface $message)
    {
        $sql = 'UPDATE %s SET'
        . ' %s = :state'
        . ',%s = :startedAt'
        . ',%s = :completedAt'
        . ',%s = :time'
        . ',%s = :memory'
        . ' WHERE %s = :id'
        ;

        $sql = sprintf($sql,
            $this->dbOptions['table'],
            $this->dbOptions['state'],
            $this->dbOptions['startedAt'],
            $this->dbOptions['completedAt'],
            $this->dbOptions['time'],
            $this->dbOptions['memory'],
            $this->dbOptions['id']
        );

        $startedAt = (null !== $message->getStartedAt()) ? $message->getStartedAt()->format('Y-m-d H:i:s') : null;
        $completedAt = (null !== $message->getCompletedAt()) ? $message->getCompletedAt()->format('Y-m-d H:i:s') : null;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':state', $message->getState(), \PDO::PARAM_INT);
        $stmt->bindParam(':startedAt', $startedAt, \PDO::PARAM_STR);
        $stmt->bindParam(':completedAt', $completedAt, \PDO::PARAM_STR);
        $stmt->bindParam(':time', $message->getTime());
        $stmt->bindParam(':memory', $message->getMemory());
        $stmt->bindParam(':id', $message->getMetadataValue('db_id'), \PDO::PARAM_INT);
        $stmt->execute();
    }

    protected function setDefaultDbOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setRequired(array(
                'table',
            ))
            ->setDefaults(array(
                'id' => 'msg_id',
                'body' => 'msg_body',
                'queue' => 'msg_queue',
                'state' => 'msg_state',
                'startedAt' => 'msg_started_at',
                'completedAt' => 'msg_completed_at',
                'priority' => 'msg_priority',
                'metadata' => 'msg_metadata',
                'time' => 'msg_time',
                'memory' => 'msg_memory',
            ))
        ;
    }
}