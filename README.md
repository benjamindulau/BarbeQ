![BarbeQ](https://raw.github.com/benjamindulau/BarbeQ/master/barbeq.jpg)

Abstract some Message Queuing system using the Adapter pattern

[![Build Status](https://travis-ci.org/benjamindulau/BarbeQ.png?branch=master)](https://travis-ci.org/benjamindulau/BarbeQ)

Work In Progress
----------------

This is a work in progress and unfinished business!

Usage example
-------------

`producer.php`

```PHP
<?php

require_once 'autoload.php';

use Symfony\Component\EventDispatcher\EventDispatcher;
use BarbeQ\BarbeQ;
use BarbeQ\Adapter\AmqpAdapter;
use BarbeQ\Model\Message;

$messageDispatcher = new EventDispatcher();
$dispatcher = new EventDispatcher();

$connection = array('host' => 'localhost');
$exchange = array('name' => 'test_direct');
$queues = array(array('name' => 'test'));

$adapter = new AmqpAdapter($connection, $exchange, $queues);
$barbeQ = new BarbeQ($adapter, $messageDispatcher, $dispatcher);

$barbeQ->cook('test', new Message(array(
    'id' => 1,
    'foo' => 'bar',
)));
// or $barbeQ->publish(...), same action
```

`consumer.php`

```PHP
<?php

require_once 'autoload.php';

use Symfony\Component\EventDispatcher\EventDispatcher;
use BarbeQ\Adapter\AmqpAdapter;
use BarbeQ\BarbeQ;
use Acme\PocBundle\Consumer\TestConsumer;

$messageDispatcher = new EventDispatcher();
$dispatcher = new EventDispatcher();

$connection = array('host' => 'localhost');
$exchange = array('name' => 'test_direct');
$queues = array(array('name' => 'test'));

$adapter = new AmqpAdapter($connection, $exchange, $queues);
$barbeQ = new BarbeQ($adapter, $messageDispatcher, $dispatcher);

$testConsumer = new TestConsumer();
$barbeQ->addConsumer('test', $testConsumer);

// Trace what's happening
$barbeQ->addListener('barbeq.pre_consume', function(ConsumeEvent $event) {
    echo sprintf("Start consuming message #%d\n", $event->getMessage()->getMetadataValue('index'));
});

$barbeQ->addListener('barbeq.post_consume', function(ConsumeEvent $event) {
    echo sprintf("Memory: %s, Time: %0.04fs\n", $event->getMessage()->getMemory(), $event->getMessage()->getTime());
});

$barbeQ->eat('test', 5);
// or $barbeQ->consume(...), same action
```

Testing
-------

```bash
$ php composer.phar update --dev
$ phpunit
```

License
-------

This bundle is under the MIT license. See the complete license in the bundle:

    LICENSE


Credits
-------

[Logo](http://www.yuminette.com/graphisme/barbeq "") by [Yuminette](http://www.yuminette.com/ "")


