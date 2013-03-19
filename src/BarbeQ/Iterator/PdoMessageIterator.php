<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Iterator;

use BarbeQ\Model\Message;
use BarbeQ\Model\MessageInterface;

class PdoMessageIterator implements MessageIteratorInterface
{
    protected $pdo;
    protected $dbOptions;
    protected $counter;
    protected $queue;
    protected $pause;
    protected $current;

    public function __construct(\PDO $pdo, $dbOptions, $queue, $pause = 500000)
    {
        $this->pdo = $pdo;
        $this->dbOptions = $dbOptions;
        $this->counter = 0;
        $this->queue = $queue;
        $this->pause = $pause;
    }

    /**
     * {@inheritDoc}
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * {@inheritDoc}
     */
    public function next()
    {
        $this->current = $this->getPendingMessage();
        $this->counter++;
    }

    /**
     * {@inheritDoc}
     */
    public function key()
    {
        return $this->counter;
    }

    /**
     * {@inheritDoc}
     */
    public function valid()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind()
    {
        $this->current = $this->getPendingMessage();
    }

    protected function getPendingMessage()
    {
        $locked = false;
        $dbOpt = $this->dbOptions;
        try {
            while (true) {
                $this->pdo->exec(sprintf('LOCK TABLES %s WRITE', $dbOpt['table']));
                $locked = true;

                $sql = sprintf('SELECT * FROM %s WHERE %s = :queue AND %s = :state ORDER BY %s DESC LIMIT 1',
                    $dbOpt['table'],
                    $dbOpt['queue'],
                    $dbOpt['state'],
                    $dbOpt['priority']
                );

                $pendingState = MessageInterface::STATE_PENDING;
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':queue', $this->queue, \PDO::PARAM_STR);
                $stmt->bindParam(':state', $pendingState, \PDO::PARAM_INT);
                $stmt->execute();
                $message = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$message) {
                    $this->pdo->exec('UNLOCK TABLES');
                    $locked = false;

                    usleep($this->pause);

                    continue;
                }

                // Updating message with "processing" state
                $sql = sprintf('UPDATE %s SET %s = :state WHERE %s = :id',
                    $dbOpt['table'],
                    $dbOpt['state'],
                    $dbOpt['id']
                );

                $processingState = MessageInterface::STATE_PROCESSING;
                $id = $message[$dbOpt['id']];
                $stmt = $this->pdo->prepare($sql);
                $stmt->bindParam(':state', $processingState, \PDO::PARAM_INT);
                $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
                $stmt->execute();

                $this->pdo->exec('UNLOCK TABLES');

                $messageObject = new Message(unserialize($message[$dbOpt['body']]), $message[$dbOpt['priority']]);
                $messageObject->setQueue($message[$dbOpt['queue']]);
                $messageObject->setState($message[$dbOpt['state']]);
                $messageObject->setMetadata(unserialize($message[$dbOpt['metadata']]));
                $messageObject->addMetadata('db_id', $message[$dbOpt['id']]);

                return $messageObject;
            }
        } catch (\Exception $e) {
            if ($locked) {
                $this->pdo->exec('UNLOCK TABLES');
            }

            throw $e;
        }
    }
}