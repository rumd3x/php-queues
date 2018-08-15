<?php
    namespace Rumd3x\Queues;

    use Exception;
    use Carbon\Carbon;

    class Queue {
        private $qid;
        private $queue;
        private $action_type;
        private $action_string;
        private $added_at;
        private $started_at;
        private $attempts;

        const ACTION_RUN_STATIC = 1;
        const ACTION_RUN_INSTANCE = 2;
        const ACTION_RUN_FILE = 3;

        public function __construct(int $action_type, int $qid = NULL) {
            $this->qid = empty($qid) ? self::generateQid() : $qid;
            $this->action_type = $action_type;
            $this->added_at = Carbon::now();
            $this->attempts = 0;
        }

        public function setAction($action) {
            $this->action_string = $action;            
            return $this;
        }

        public function execute() {            
            $success = true;
            try {
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
            } catch (Exception $e) {
                $success = false;
            }     
            return $success;
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
                'qid' => $this->qid,
                'queue_name' => $this->queue,
                'action' => $this->action_string,
                'action_type' => $this->action_type,
                'started_at' => empty($this->started_at) ? null : $this->started_at->format('Y-m-d H:i:s'),
                'added_at' => empty($this->added_at) ? null : $this->added_at->format('Y-m-d H:i:s'),
                'attempts' => $this->attempts,
            ];
        }

        public function getQueueName() {
            return $this->queue;
        }

        public function getAction() {
            return [
                'type' => $this->action_type,
                'action' => $this->action_string,
            ];
        }

        public function getAddedAt() {
            return $this->added_at;
        }

        public function setStarted() {
            if (!isset($this->started_at)) $this->started_at = Carbon::now();
            $this->attempts++;
            return $this;
        }

        public function getQid() {
            if (empty($this->qid)) { $this->qid = self::generateQid(); }
            return $this->qid;
        }

        private static function generateQid() {
            $qm = QueueManager::getInstance();
            $queues = array_merge($qm->getQueued(), $qm->getRunning());
            $qids = [];
            foreach($queues as $queue) {
                $qids[] = $queue->qid;
            }
            if (empty($qids)) {
                $qid = 0;
            } else {
                $qid = max($qids);
            }
            $qid++;
            return $qid;
        }

        public static function parse($queue) {
            $queue = (Array) $queue;
            $queueObj = new static($queue['action_type'], $queue['qid']);
            $queueObj->queue = $queue['queue_name'];
            $queueObj->action_string = $queue['action'];
            $queueObj->started_at = Carbon::createFromFormat('Y-m-d H:i:s', $queue['started_at']);
            $queueObj->added_at = Carbon::createFromFormat('Y-m-d H:i:s', $queue['added_at']);
            $queueObj->attempts = $queue['attempts'];
            $queueObj->validate();
            return $queueObj;
        }

        public static function parseMultiple(array $array_of_queue) {
            $return_array = [];
            foreach($array_of_queue as $queue) $return_array[] = self::parse($queue);
            return $return_array;
        }

        public function validate() {
            if (empty($this->action_string)) { throw new Exception("Queue action string cannot be empty"); }
            if (empty($this->action_string)) { throw new Exception("Queue action type cannot be empty"); }
            if (!in_array($this->action_type, [Queue::ACTION_RUN_STATIC, Queue::ACTION_RUN_INSTANCE, Queue::ACTION_RUN_FILE])) {
                throw new Exception("Invalid queue action type");
            }
            if (empty($this->queue)) { throw new Exception("Queue name cannot be empty"); }
            if (empty($this->qid)) { $this->qid = self::generateQid(); }
            if (empty($this->attempts)) { $this->attempts = 0; }
            if (empty($this->added_at)) { $this->added_at = Carbon::now(); }
            if (empty($this->started_at)) { $this->started_at = null; }
            return $this;
        }


    }
