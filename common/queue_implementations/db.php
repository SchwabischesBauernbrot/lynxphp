<?php

include 'base.php';

// another type where two levels would help
class db_queue_driver extends queue_implementation_base_class implements queue_implementation_interface {
  function __construct() {
    $this->queue_model = array(
      'name'   => 'queue',
      'fields' => array(
        'queue' => array('type' => 'str'),
        'type'  => array('type' => 'str'),
        'job'   => array('type' => 'text'),
        //'reads'   => array('type' => 'int'),
        // how would we handle broadcast expiration?
        // we'd need to register all the active listeners
        // but in a web framework theoretic workers and active workers are two different things
        // a subscriber vs a timeout...
        // separate table?
        //'ack'   => array('type' => 'int'),
        // I'm not sure the finished_at flag should be part of this queue
        //'finished_at'   => array('type' => 'int'),
      )
    );
    $this->modelReady = false;
    $this->type = 'direct'; // vs broadcast
  }

  function subscribe($queue) {
    // lazy load as needed
    if (!$this->modelReady) {
      global $db;
      $db->autoupdate($this->queue_model);
      $this->modelReady = true;
    }
  }

  // limit? get one? get all?
  // it's getting one rn
  function receive($queue, $waitformsg = false) {
    global $db;
    // if we delete, we should only retrieve one at a time...
    // only write back if we need to retry?
    //'criteria'=>array('finished_at' => 0)
    $res = $db->find($this->queue_model, array('order' => 'queueid asc', 'limit'=>1));
    $workitems = array();
    $ids = array();

    if (!$db->num_rows($res)) {
      // nothing in queue
      $db->free($res);
      return NULL;
    }

    //while($row = $db->get_row($res)) {
    $row = $db->get_row($res);
    //$row['reads']++;
    //$db->updateById($this->queue_model, $row['queueid'], array('reads' => $row['reads']));

    $workitems[$row['queueid']] = json_decode($row['job'], true);
      //$ids[] = $row['id'];
    //}
    $db->free($res);
    if ($row['type'] === 'direct') {
      $db->deleteById($this->queue_model, $row['queueid']);
    }
    /*
    $db->delete($this->queue_model, array('criteria'=>array(
      'id' => $ids
    )));
    */
    return $workitems[$row['queueid']];
  }

  // write to the queue
  function send($queue, $message) {
    global $db;
    // if we passed in an id, then we're signaling the job is done
    /*
    if (!empty($message['id'])) {
      global $now;
      return $db->update($this->queue_model, array('finished_at'=>$now), array('criteria' => array(
        'id' => $message['id']
      )));
    }
    */
    return $db->insert($this->queue_model, array(
      array(
        'queue' => $queue,
        'job'   => json_encode($message),
        'type'  => $this->type,
        //'finished_at' => 0,
      ),
    ));
  }
}

?>