<?php

// both backend and frontend should have queues
// frontend can't rely on backend to provide queueing?
// well it could...
// we would need a per frontend container...

// persisent across requests
// so like sessions but unique across the app not browser

// could also plug into the cache/db drivers for backing stores...

// table
// pub/sub? lists

// pub/sub
interface queue_implementation_interface {
  function subscribe($queue);
  function receive($queue, $waitformsg=false);
  function send($queue, $message);

}

class queue_implementation_base_class implements queue_implementation_interface {
  // clear queue?
  // are these storaged or not?
  // maybe an event vs task?
  function subscribe($queue) {
  }
  function receive($queue, $waitformsg=false) {
    $msgs=$this->get('queue:'.$queue);
    $this->clear('queue:'.$queue);
    return $msgs;
  }
  function send($queue,$message) {
    $msgs=$this->get('queue:'.$queue);
    $msgs[]=$message;
    $this->set('queue:'.$queue, $msgs);
  }
}

?>
