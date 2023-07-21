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

/*
{
  "pipeline":"pipeline_wq_file_add",
  "params":{
    "postid":"515",
    "sha256":"ff13a784118058022f3d402646e17b9b5d1f2795c1252a262891e1057cd62724",
    "path":"storage\/boards\/endchan\/75\/515_0.png",
    "browser_type":"image\/png",
    "mime_type":"image\/png",
    "type":"image",
    "filename":"1685698269724.png",
    "size":"743836",
    "ext":"png",
    "w":"890",
    "h":"890",
    "filedeleted":"0",
    "spoiler":"0",
    "tn_w":"226",
    "tn_h":"226",
    "fileid":"188",
    "boardUri":"endchan"
  }
}
*/
  function getAnalytics($queue) {
    global $db;
    // queue/type ('queue'/'direct') are all the same
    // job is different...
    // select type, count(*) from queues group by type;
    $res = $db->find($this->queue_model, array(
      'fields' => array('job'),
    ));
    $qByPipe = array();
    while($row = $db->get_row($res)) {
      $data = json_decode($row['job'], true);
      $pl = $data['pipeline'];
      //print_r($data);
      if (!isset($qByPipe[$pl])) $qByPipe[$pl] = 0;
      $qByPipe[$pl]++;
    }
    return $qByPipe;
  }

  function getCount($queue) {
    global $db;
    return $db->count($this->queue_model);
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
    // we change to delete on job fetch
    // can be re-inserted on failure
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