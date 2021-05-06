<?php

// both backend and frontend should have queues
// frontend can't rely on backend to provide queueing?
// well it could...
// we would need a per frontend container...

// could also plug into the cache/db drivers for backing stores...

// pub/sub
interface queue_implementation_interface {
  function subscribe($queue);
  function receive($queue, $waitformsg = false);
  function send($queue, $message);
}

class queue_implementation_base_class implements queue_implementation_interface {
  // clear queue?
  // are these storaged or not?
  // maybe an event vs task?

  // I'm interesting in listening to this queue
  function subscribe($queue) {
  }
  // consume queue
  function receive($queue, $waitformsg = false) {
  }
  // send to this queue
  function send($queue, $message) {
  }
}

?>
