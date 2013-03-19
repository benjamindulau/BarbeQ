<?php

namespace BarbeQ\Tests;

use BarbeQ\BarbeQ;

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
        $this->messageDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcher');

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
}