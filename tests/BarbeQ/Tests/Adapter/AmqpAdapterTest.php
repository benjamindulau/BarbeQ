<?php

/**
 * This file is part of BarbeQ
 *
 * (c) anonymation <contact@anonymation.com>
 *
 */
namespace BarbeQ\Tests\Adapter;

use BarbeQ\Adapter\AmqpAdapter;
use BarbeQ\Model\Message;
use BarbeQ\Model\MessageInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use RabbitMQ\Management\APIClient;
use RabbitMQ\Management\Entity\Queue;
use RabbitMQ\Management\HttpClient;

class AmqpAdapterTest extends \PHPUnit_Framework_TestCase
{
    const TEST_QUEUE = 'ano_amq.tests.sausage_queue';
    const TEST_EXCHANGE = 'ano_amq.tests.sausage_exchange';

    protected $connectionOptions;
    protected $testQueueOptions;
    protected $testExchangeOptions;

    /**
     * @var MessageInterface
     */
    protected $testMessage;

    /**
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * @var AMQPChannel
     */
    protected $channel;

    /**
     * @var APIClient
     */
    protected $apiClient;

    protected function setUp()
    {
        $this->connectionOptions = array(
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
        );

        $this->testQueueOptions = array(
            array('name' => self::TEST_QUEUE)
        );
        $this->testExchangeOptions = array('name' => self::TEST_EXCHANGE);

        $this->testMessage = new Message(array('dummy'));

        $this->connection = new AMQPConnection(
            $this->connectionOptions['host'],
            $this->connectionOptions['port'],
            $this->connectionOptions['user'],
            $this->connectionOptions['password'],
            $this->connectionOptions['vhost']
        );

        $this->channel = $this->connection->channel();

        $client = HttpClient::factory(array(
            'host' => 'localhost'
        ));
        $this->apiClient = new APIClient($client);
    }

    public function tearDown()
    {
        unset($this->connectionOptions);
        unset($this->testExchangeOptions);
        unset($this->testQueueOptions);
        unset($this->testMessage);

        $this->channel->close();
        $this->connection->close();
        unset($this->channel);
        unset($this->connection);
    }

    public function testConnectionDefaultOptions()
    {
        $adapter = new AmqpAdapter(array(), array(), $this->testQueueOptions);
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

        $adapter = new AmqpAdapter($connection, array(), $this->testQueueOptions);
        $property = new \ReflectionProperty($adapter, 'connection');
        $property->setAccessible(true);

        $this->assertEquals($connection, $property->getValue($adapter));
    }

    public function testExchangeDefaultOptions()
    {
        $adapter = new AmqpAdapter(
            $this->connectionOptions,
            array(), // exchange
            $this->testQueueOptions
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
            $this->testQueueOptions
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
            $this->testQueueOptions
        );

        $property = new \ReflectionProperty($adapter, 'queues');
        $property->setAccessible(true);
        $expectedValues = array(
            'name' => self::TEST_QUEUE,
            'passive' => false,
            'durable' => true,
            'exclusive' => false,
            'auto_delete' => false,
            'nowait' => false,
            'arguments' => null,
            'ticket' => null
        );

        $resultingQueues = $property->getValue($adapter);
        $this->assertEquals($expectedValues, $resultingQueues[self::TEST_QUEUE]);
    }

    public function testQueueOptionsOverride()
    {
        $queue = array(
            'name' => self::TEST_QUEUE,
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
        $this->assertEquals($queue, $resultingQueues[self::TEST_QUEUE]);
    }

    public function testQueueIsDeclaredOnPublish()
    {
        $adapter = $this->createTestAdapter();
        $adapter->publish(self::TEST_QUEUE, $this->testMessage);

        // test
        $queue = $this->apiClient->getQueue('/', self::TEST_QUEUE);
        $this->assertEquals($queue->name, self::TEST_QUEUE);

        $this->cleanUpQueuesAndExchanges();
    }

    public function testExchangeIsDeclaredOnPublish()
    {
        $adapter = $this->createTestAdapter();
        $adapter->publish(self::TEST_QUEUE, $this->testMessage);

        // test
        $exchange = $this->apiClient->getExchange('/', self::TEST_EXCHANGE);
        $this->assertEquals($exchange->name, self::TEST_EXCHANGE);

        $this->cleanUpQueuesAndExchanges();
    }

    public function testQueueAndExchangeAreBindedOnPublish()
    {
        $adapter = $this->createTestAdapter();
        $adapter->publish(self::TEST_QUEUE, $this->testMessage);

        $bindings = $this->apiClient->listBindingsByExchangeAndQueue(
            '/',
            self::TEST_EXCHANGE,
            self::TEST_QUEUE
        );
        foreach($bindings as $binding) {
            $this->assertEquals(self::TEST_EXCHANGE, $binding->source);
            $this->assertEquals(self::TEST_QUEUE, $binding->destination);
        }

        $this->cleanUpQueuesAndExchanges();
    }

    /**
     * @return Queue
     */
    protected function getTestQueueEntity()
    {
        $queue = new Queue();
        $queue->name = self::TEST_QUEUE;
        $queue->vhost = '/';

        return $queue;
    }

    protected function cleanUpQueuesAndExchanges()
    {
        // cleanup
        $this->apiClient->deleteQueue('/', self::TEST_QUEUE);
        $this->apiClient->deleteExchange('/', self::TEST_EXCHANGE);
    }

    /**
     * @return \BarbeQ\Adapter\AmqpAdapter
     */
    protected function createTestAdapter()
    {
        return new AmqpAdapter($this->connectionOptions, $this->testExchangeOptions, $this->testQueueOptions);
    }
}