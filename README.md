![BarbeQ](https://raw.github.com/benjamindulau/BarbeQ/master/barbeq.jpg)

Abstract some Message Queuing system using the Adapter pattern

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

$iterations = 3;
$i = 0;
foreach ($barbeQ->getMessages('test') as $message) {
    $i++;
    echo sprintf("Iteration #%d\n", $i);

    $barbeQ->eat($message);
    // or $barbeQ->consume(...), same action

    echo sprintf("Memory: %s, Time: %0.04fs\n", $message->getMemory(), $message->getTime());

    if ($i >= $iterations) {
        $barbeQ->stopConsuming();
        break;
    }
}
```


