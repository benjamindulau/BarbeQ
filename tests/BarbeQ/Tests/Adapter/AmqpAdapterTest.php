<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Tests\Adapter;

use BarbeQ\Adapter\AmqpAdapter;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;

class AmqpAdapterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $connectionOptions;

    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $dummyQueueOptions;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    protected function setUp()
    {
        $this->connectionOptions = array(
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
        );

        $this->dummyQueueOptions = array(
            array('name' => 'test')
        );
    }

    public function tearDown()
    {
        unset($this->connectionOptions);
    }

    public function testConnectionDefaultOptions()
    {
        $adapter = new AmqpAdapter(array(), array(), $this->dummyQueueOptions);
        $property = new \ReflectionProperty($adapter, 'connection');
        $property->setAccessible(true);
        $expectedValues = array(
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
        );

        $this->assertEquals($expectedValues, $property->getValue($adapter));
    }

    public function testConnectionOptionsOverride()
    {
        $connection = array(
            'host' => '192.168.0.20',
            'port' => 3180,
            'user' => 'foo',
            'password' => 'bar',
            'vhost' => 'myvhost',
        );

        $adapter = new AmqpAdapter($connection, array(), $this->dummyQueueOptions);
        $property = new \ReflectionProperty($adapter, 'connection');
        $property->setAccessible(true);

        $this->assertEquals($connection, $property->getValue($adapter));
    }

    public function testExchangeDefaultOptions()
    {
        $adapter = new AmqpAdapter(
            $this->connectionOptions,
            array(), // exchange
            $this->dummyQueueOptions
        );

        $property = new \ReflectionProperty($adapter, 'exchange');
        $property->setAccessible(true);
        $expectedValues = array(
            'name' => '',
            'type' => 'direct',
            'passive' => false,
            'durable' => true,
            'auto_delete' => false,
            'internal' => false,
            'nowait' => false,
            'arguments' => null,
            'ticket' => null,
        );

        $this->assertEquals($expectedValues, $property->getValue($adapter));
    }

    public function testExchangeOptionsOverride()
    {
        $exchange = array(
            'name' => 'my_exchange',
            'type' => 'fanout',
            'passive' => true,
            'durable' => false,
            'auto_delete' => true,
            'internal' => true,
            'nowait' => true,
            'arguments' => 'some arguments',
            'ticket' => 'a ticket',
        );

        $adapter = new AmqpAdapter(
            $this->connectionOptions,
            $exchange, // exchange
            $this->dummyQueueOptions
        );

        $property = new \ReflectionProperty($adapter, 'exchange');
        $property->setAccessible(true);

        $this->assertEquals($exchange, $property->getValue($adapter));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     */
    public function testQueueOptionNameRequired()
    {
        $queues = array(
            array('durable' => true),
            array('durable' => false),
        );

        $adapter = new AmqpAdapter(
            $this->connectionOptions,
            array(), // exchange
            $queues
        );
    }

    public function testQueueDefaultOptions()
    {
        $adapter = new AmqpAdapter(
            $this->connectionOptions,
            array(), // exchange
            $this->dummyQueueOptions
        );

        $property = new \ReflectionProperty($adapter, 'queues');
        $property->setAccessible(true);
        $expectedValues = array(
            'name' => 'test',
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
            'nowait' => false,
            'arguments' => null,
            'ticket' => null
        );

        $resultingQueues = $property->getValue($adapter);
        $this->assertEquals($expectedValues, $resultingQueues['test']);
    }

    public function testQueueOptionsOverride()
    {
        $queue = array(
            'name' => 'test',
            'passive' => true,
            'durable' => false,
            'exclusive' => true,
            'auto_delete' => true,
            'nowait' => true,
            'arguments' => 'some arguments',
            'ticket' => 'a ticket',
        );

        $adapter = new AmqpAdapter(
            $this->connectionOptions,
            array(), // exchange
            array($queue)
        );

        $property = new \ReflectionProperty($adapter, 'queues');
        $property->setAccessible(true);

        $resultingQueues = $property->getValue($adapter);
        $this->assertEquals($queue, $resultingQueues['test']);
    }

}