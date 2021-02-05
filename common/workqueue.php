<?php

$queue_type_class = 'db' . '_queue_driver';

class work_queue {
  function __construct() {
    global $queue_type_class;
    // I think we're only going to need one queue
    $this->queue = new $queue_type_class;
    $this->queueName = 'queue';
    $this->subscribed = false;
  }
  private function checkSub() {
    if (empty($this->subscribed[$this->queueName])) {
      $this->queue->subscribe($this->queueName);
      $this->subscribed[$this->queueName] = true;
    }
  }
  function getWork() {
    $this->checkSub();
    // pipeline, params
    $msg = $this->queue->receive($this->queueName);
    if ($msg === NULL) {
      // nothing in the queue
      return;
    }
    // we could dispatch to the pipeline
    // and if we have a result how to we pass it to the next
    // when rn we're just only allowing no output

    global $pipelines;
    if (!isset($pipelines[$msg['pipeline']])) {
      // should we put it back onto the stack?
      echo "No such pipeline [", $msg['pipeline'], "]<br>\n";
      return;
    }
    $pipelines[$msg['pipeline']]->execute($msg['params']);

    return $msg;
  }
  function addWork($pipeline, $params) {
    $this->checkSub();
    $msg = array(
       // this is the pipeline to execute
      'pipeline' => $pipeline,
      'params'   => $params,
      // don't need this if queue is self-destructing/expiring
      //'finished_at' => 0,
    );
    $this->queue->send($this->queueName, $msg);
  }
}

?>
