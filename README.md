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
```php
use Rumd3x\Queues\Queue;
$queue = new Queue(Queue::ACTION_RUN_FILE);
$queue->setAction('/var/www/test.php')->pushTo('example_queue');
```


### Running the Queues
You should add the code below to a php script and run it with no timeout
```php
use Rumd3x\Queues\QueueManager;
$manager = QueueManager::getInstance();
while (true) {
    $manager->runQueued();
}
```
