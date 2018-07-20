<?php
    namespace Rumd3x\Queues;

    use Exception;
    use DateTime;

    class Queue {
        private $queue;
        private $action_type;
        private $action_string;
        private $added_at;
        private $started_at;

        const ACTION_RUN_STATIC = 1;
        const ACTION_RUN_INSTANCE = 2;
        const ACTION_RUN_FILE = 3;

        public function __construct(int $action_type) {
            $this->action_type = $action_type;
            $this->added_at = date('Y-m-d H:i:s');
        }

        public function setAction(string $action) {
            $this->action_string = $action;            
            return $this;
        }

        public function execute() {
            $this->validate();
            if ($this->action_type === self::ACTION_RUN_STATIC) {
                $namespace = $this->action_string;
                $namespace::run();
            }
            if ($this->action_type === self::ACTION_RUN_INSTANCE) {
                $instance = new $this->action_string;
                $instance->run();
            }
            if ($this->action_type === self::ACTION_RUN_FILE) {
                include $this->action_string;
            }
            return $this;
        }

        public function pushTo(string $queue) {
            $this->queue = $queue;
            $this->validate();
            $qm = QueueManager::getInstance();
            $qm->addToQueue($this);
            return $this;
        }

        public function toArray() {
            return [
                'queue_name' => $this->queue,
                'action' => $this->action_string,
                'action_type' => $this->action_type,
                'started_at' => $this->started_at,
                'added_at' => $this->added_at,
            ];
        }

        public function getQueueName() {
            return $this->queue;
        }

        public function getAddedAtAsDateTime() {
            return DateTime::createFromFormat('Y-m-d H:i:s', $this->added_at);
        }

        public function setStarted() {
            $this->started_at = date('Y-m-d H:i:s');
            return $this;
        }

        public static function parse($queue) {
            $queue = (Array) $queue;
            $queueObj = new self($queue['action_type']);
            $queueObj->queue = $queue['queue_name'];
            $queueObj->action_string = $queue['action'];
            $queueObj->started_at = $queue['started_at'];
            $queueObj->added_at = $queue['added_at'];
            return $queueObj;
        }

        public static function parseMultiple(array $array_of_queue) {
            $return_array = [];
            foreach($array_of_queue as $queue) $return_array[] = self::parse($queue);
            return $return_array;
        }

        public function validate() {
            if (empty($this->action_string)) throw new Exception("Queue action string cannot be empty");
            if (empty($this->action_string)) throw new Exception("Queue action type cannot be empty");
            if (!in_array($this->action_type, [Queue::ACTION_RUN_STATIC, Queue::ACTION_RUN_INSTANCE, Queue::ACTION_RUN_FILE]))
                throw new Exception("Invalid queue action type");
            if (empty($this->queue)) throw new Exception("Queue name cannot be empty");
            return $this;
        }


    }