<?php

namespace BarbeQ\Tests;

use BarbeQ\BarbeQ;
use BarbeQ\Model\Message;
use Symfony\Component\EventDispatcher\EventDispatcher;

class BarbeQTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \BarbeQ\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $messageDispatcher;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var BarbeQ
     */
    protected $barbeQ;


    protected function setUp()
    {
        $this->adapter = $this->getMock('BarbeQ\Adapter\AdapterInterface');
        $this->messageDispatcher = new EventDispatcher();
        $this->dispatcher = new EventDispatcher();

        $this->barbeQ = new BarbeQ($this->adapter, $this->messageDispatcher, $this->dispatcher);
    }

    protected function tearDown()
    {
        unset($this->adapter);
        unset($this->messageDispatcher);
        unset($this->dispatcher);
        unset($this->barbeQ);
    }

    public function testGetAdapter()
    {
        $this->assertSame($this->adapter, $this->barbeQ->getAdapter());
    }

    public function testAddAndGetConsumers()
    {
        $consumer = $this->getMock('BarbeQ\Consumer\ConsumerInterface');
        $consumer2 = $this->getMock('BarbeQ\Consumer\ConsumerInterface');
        $this->barbeQ->addConsumer('foo', $consumer);
        $this->barbeQ->addConsumer('bar', $consumer2);

        $this->assertCount(2, $this->barbeQ->getConsumers());
    }

    public function testGetConsumersForQueue()
    {
        $consumer = $this->getMock('BarbeQ\Consumer\ConsumerInterface');
        $consumer2 = $this->getMock('BarbeQ\Consumer\ConsumerInterface');
        $this->barbeQ->addConsumer('foo', $consumer);
        $this->barbeQ->addConsumer('bar', $consumer2);

        $this->assertCount(1, $this->barbeQ->getConsumers('foo'));
    }

    public function testCookSetQueueOnMessage()
    {
        $message = new Message();
        $this->barbeQ->cook('bar', $message);

        $this->assertEquals('bar', $message->getQueue());
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage "sausage" event does not exist in BarbeQ.
     */
    public function testAddListenerOnInvalidEvent()
    {
        $this->barbeQ->addListener('sausage', function() {});
    }
}