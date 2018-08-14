# php-queues
A nice and easy to use PHP utility for handling queues and script executing without overlapping.


## Installation
To install via composer add this to your composer.json
```json
"minimum-stability": "dev",
"repositories": [
	{ "type": "git", "url": "https://github.com/rumd3x/php-queues.git" }
]
```
And then run
```sh
  composer require "rumd3x/php-queues:*"
```


## Usage
### Adding a Queue
There are three ways of adding a script to the execution queue.

The first one is to pass a full path to a php script to be executed:
```php
use Rumd3x\Queues\Queue;
$queue = new Queue(Queue::ACTION_RUN_FILE);
$queue->setAction('/var/www/test.php')->pushTo('example_queue');
```

The other two you should pass the full namespace of a PHP class.


The ACTION_RUN_STATIC action type will try to call the run method on the class statically:
```php
use Rumd3x\Queues\Queue;
$queue = new Queue(Queue::ACTION_RUN_STATIC);
$queue->setAction("\\App\\Synchronizer")->pushTo('sync');
// This is equal to calling \App\Synchronizer::run();
```

The ACTION_RUN_INSTANCE action type will try to call the run method on the class after instantiating it:
```php
use Rumd3x\Queues\Queue;
$queue = new Queue(Queue::ACTION_RUN_INSTANCE);
$queue->setAction("\\App\\Synchronizer")->pushTo('sync');
// This is equal to doing 
// $sync = new \App\Synchronizer;
// $sync->run();
```


### Running the Queues
You should add the code below to a php script and run it with no timeout
```php
use Rumd3x\Queues\QueueManager;
$manager = QueueManager::getInstance();
while (true) {
    $manager->runAll();
}
```


Or you could also make multiple executors for each of your queues so they run in parallel.
```php
use Rumd3x\Queues\QueueManager;
$manager = QueueManager::getInstance();
while (true) {
    $manager->run('example_queue');
}
```

### Managing queues
You also can check yourself the current state of the queues during execution time.
```php
use Rumd3x\Queues\QueueManager;
use Rumd3x\Queues\Queue;

$manager = QueueManager::getInstance();

$queue = new Queue(Queue::ACTION_RUN_FILE);
$queue->setAction('/var/www/test.php');

$manager->isQueued($queue); // returns true or false
$manager->isRunning($queue); // returns true or false
$manager->isQueueFree('sync'); // returns true or false
```
