<?php

include 'base.php';

// another type where two levels would help
class db_queue_driver extends queue_implementation_base_class implements queue_implementation_interface {
  function __construct() {
  }
}

?>