<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Adapter;

use BarbeQ\Model\Message;
use BarbeQ\Model\MessageInterface;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AmqpAdapter implements AdapterInterface
{
    protected $connection;
    protected $exchange;
    protected $queues;
    protected $options;
    protected $channel;

    protected $exchangeDeclared = false;

    public function __construct(array $connection, array $exchange = array(), array $queues, array $options = array())
    {
        $this->resolveConnection($connection);
        $this->resolveExchange($exchange);
        $this->resolveQueues($queues);
        $this->resolveOptions($options);
    }

    /**
     * {@inheritDoc}
     */
    public function publish($queue, MessageInterface $message)
    {
        $this->declareEverything();

        $body = serialize($message->getBody());

        $msg = new AMQPMessage($body, array(
            'content_type' => $this->options['content_type'],
            'delivery_mode' => $this->options['delivery_mode'],
        ));

        $this->getChannel()->basic_publish($msg, $this->exchange['name'], $queue);
    }

    public function getMessageIterator()
    {
        // TODO: Implement getMessageIterator() method.
    }

    public function onSuccess(MessageInterface $message)
    {
        // TODO: Implement onSuccess() method.
    }

    public function onError(MessageInterface $message)
    {
        // TODO: Implement onError() method.
    }

    public function declareEverything()
    {
        if (!$this->exchangeDeclared) {
            $this->getChannel()->exchange_declare(
                $this->exchange['name'],
                $this->exchange['type'],
                $this->exchange['passive'],
                $this->exchange['durable'],
                $this->exchange['auto_delete'],
                $this->exchange['internal'],
                $this->exchange['nowait'],
                $this->exchange['arguments'],
                $this->exchange['ticket']
            );

            $this->exchangeDeclared = true;
        }
        
        foreach($this->queues as $queue) {
            if (!isset($queue['declared']) || !$queue['declared']) {
                list($queueName, ,) = $this->getChannel()->queue_declare(
                    $queue['name'],
                    $queue['passive'],
                    $queue['durable'],
                    $queue['exclusive'],
                    $queue['auto_delete'],
                    $queue['nowait'],
                    $queue['arguments'],
                    $queue['ticket']
                );
                $queue['declared'] = true;

                // binding queue to exchange
                $this->getChannel()->queue_bind($queueName, $this->exchange['name'], $queueName);
            }
        }
    }

    public function getChannel()
    {
        if (!$this->channel) {
            $this->connection = new AMQPConnection(
                $this->connection['host'],
                $this->connection['port'],
                $this->connection['user'],
                $this->connection['pass'],
                $this->connection['vhost']
            );

            $this->channel = $this->connection->channel();
        }

        return $this->channel;
    }

    protected function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(array(
                'delivery_mode' => 2,
                'content_type' => 'text/plain',
            ))
            ->setAllowedValues(array(
                'delivery_mode' => array(1, 2),
            ))
        ;

        $this->options = $resolver->resolve($options);
    }

    protected function resolveConnection(array $connection)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired(array(
                'host',
                'port',
                'user',
                'password',
                'vhost',
            ))
            ->setDefaults(array(
                'host' => 'localhost',
                'port' => 5672,
                'user' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
            ))
        ;

        $this->connection = $resolver->resolve($connection);
    }

    protected function resolveExchange(array $exchange)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefaults(array(
                'name' => '',
                'type' => 'direct',
                'passive' => false,
                'durable' => true,
                'auto_delete' => false,
                'internal' => false,
                'nowait' => false,
                'arguments' => null,
                'ticket' => null,
            ))
        ;

        $this->exchange = $resolver->resolve($exchange);
    }

    protected function resolveQueues(array $queues)
    {
        foreach($queues as $queue) {
            $resolver = new OptionsResolver();
            $resolver
                ->setRequired(array(
                    'name',
                ))
                ->setDefaults(array(
                    'passive' => false,
                    'durable' => true,
                    'exclusive' => false,
                    'auto_delete' => false,
                    'nowait' => false,
                    'arguments' => null,
                    'ticket' => null
                ))
            ;

            $queue = $resolver->resolve($queue);
            $this->queues[$queue['name']] = $queue;
        }
    }
}