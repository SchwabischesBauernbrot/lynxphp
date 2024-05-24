<?php

$queue_type_class = 'db' . '_queue_driver';

class work_queue {
  function __construct() {
    global $queue_type_class;
    // I think we're only going to need one queue
    $this->queue = new $queue_type_class;
    $this->queueName = 'queue';
    $this->subscribed = array();
  }
  private function checkSub() {
    if (empty($this->subscribed[$this->queueName])) {
      $this->queue->subscribe($this->queueName);
      $this->subscribed[$this->queueName] = true;
    }
  }

  function getAnalytics() {
    return $this->queue->getAnalytics($this->queueName);
  }

  function getWorkCount() {
    return $this->queue->getCount($this->queueName);
  }

  function getWork() {
    $this->checkSub();
    // pipeline, params
    $msgs = $this->queue->receive($this->queueName);
    // is one or multiple (received says its returning multiple)
    // it has to be one...
    //print_r($msgs);
    if ($msgs === NULL) {
      // nothing in the queue
      return;
    }
    // we could dispatch to the pipeline
    // and if we have a result how to we pass it to the next
    // when rn we're just only allowing no output

    global $pipelines;
    if (!isset($pipelines[$msgs['pipeline']])) {
      // should we put it back onto the stack?
      echo "No such pipeline [", $msgs['pipeline'], "]<br>\n";
      return;
    }
    $pipelines[$msgs['pipeline']]->execute($msgs['params']);

    return $msgs;
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
