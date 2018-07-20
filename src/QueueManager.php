<?php
    namespace Rumd3x\Queues;

    use Exception;

    class QueueManager {
        private static $instance;
        private static $driver_filename = 'queues.json';
        private $running = [];
        private $queued = [];

        private function __construct() {
            try {
                if (!file_exists(self::$driver_filename)) {
                    $this->updateDriverFile();
                } else {
                    $this->readDriverFile();
                }            
            } catch (Exception $e) {
                $this->updateDriverFile();
            }

        }

        public static function getInstance() {
            if (!isset(self::$instance)) {
                self::$instance = new self;
            }
            return self::$instance;
        }

        public function runAll() {
            $this->readDriverFile();
            foreach($this->queued as $key => $queue) {
                if ($this->isQueueFree($queue->getQueueName(), false)) {
                    $this->running[] = $queue;
                    unset($this->queued[$key]);
                    $this->queued = array_values($this->queued);
                    $this->updateDriverFile();
                }
            }
            $this->sortRunning();
            foreach ($this->running as $key => $queue) {
                $queue = $queue->setStarted();
                $this->updateDriverFile();
                $queue->execute();
                unset($this->running[$key]);
                $this->running = array_values($this->running);
                $this->updateDriverFile();
            }
            return $this;
        }

        public function run(string $queue_name) {
            $this->readDriverFile();
            foreach($this->queued as $key => $queue) {
                if ($queue->getQueueName() === $queue_name) {
                    if ($this->isQueueFree($queue_name, false)) {
                        $this->running[] = $queue;
                        unset($this->queued[$key]);
                        $this->queued = array_values($this->queued);
                        $this->updateDriverFile();
                    }
                }
            }
            $this->sortRunning();
            foreach ($this->running as $key => $queue) {
                if ($queue->getQueueName() === $queue_name) {
                    $queue = $queue->setStarted();
                    $this->updateDriverFile();
                    $queue->execute();
                    unset($this->running[$key]);
                    $this->running = array_values($this->running);
                    $this->updateDriverFile();
                }
            }
            return $this;
        }

        private function sortRunning() {
            usort($this->running, function($a, $b) {
                if ($a->getAddedAtAsDateTime() == $b->getAddedAtAsDateTime()) return 0;
                if ($a->getAddedAtAsDateTime() > $b->getAddedAtAsDateTime()) return 1;
                if ($a->getAddedAtAsDateTime() < $b->getAddedAtAsDateTime()) return -1;
            });
            return $this;
        }

        private function sortQueues() {
            usort($this->queued, function($a, $b) {
                if ($a->getAddedAtAsDateTime() == $b->getAddedAtAsDateTime()) return 0;
                if ($a->getAddedAtAsDateTime() > $b->getAddedAtAsDateTime()) return 1;
                if ($a->getAddedAtAsDateTime() < $b->getAddedAtAsDateTime()) return -1;
            });
            return $this;
        }

        public function isQueueFree(string $queue_name, bool $reread = true) {
            $is_free = true;
            if ($reread) { $this->readDriverFile(); }
            foreach($this->running as $queue) {
                if ($queue->getQueueName() === $queue_name) {
                    $is_free = false;
                    break;
                }
            }
            return booval($is_free);
        }

        public function isQueued(Queue $queue, $reread = true) {
            $is_queued = false;
            if ($reread) { $this->readDriverFile(); }
            foreach($this->queued as $queued) {
                if ($queued->getAction() === $queue->getAction()) {
                    $is_queued = true;
                    break;
                }
            }
            return booval($is_queued);
        }

        public function isRunning(Queue $queue, $reread = true) {
            $is_running = false;
            if ($reread) { $this->readDriverFile(); }
            foreach($this->running as $running) {
                if ($running->getAction() === $queue->getAction()) {
                    $is_running = true;
                    break;
                }
            }
            return booval($is_running);
        }

        private function readDriverFile() {
            $contents = file_get_contents(self::$driver_filename, FILE_TEXT);
            $driver_data = json_decode($contents);
            if (!isset($driver_data->running) || !is_array($driver_data->running)) {
                throw new Exception("Queues file doesnt have a valid array in the running property.");
            }
            if (!isset($driver_data->queued) || !is_array($driver_data->queued)) {
                throw new Exception("Queues file doesnt have a valid array in the queued property.");
            }
            $this->running = Queue::parseMultiple($driver_data->running);
            $this->queued = Queue::parseMultiple($driver_data->queued);
        }

        private function updateDriverFile() {
            file_put_contents(self::$driver_filename, $this->toJson(), LOCK_EX | FILE_TEXT);
            return $this;
        }

        public function addToQueue(Queue $queue) {
            $this->queued[] = $queue;
            $this->sortQueues();
            return $this->updateDriverFile();
        }

        public function getRunning() {
            return $this->running;
        }

        public function getQueued() {
            return $this->queued;
        }

        public function toJson() {
            $json = [
                'running' => [],
                'queued' => [],
            ];
            foreach($this->running as $queueObj) { $json['running'][] = $queueObj->toArray(); }
            foreach($this->queued as $queueObj) { $json['queued'][] = $queueObj->toArray(); }
            return json_encode($json, JSON_PRETTY_PRINT);
        }


    }