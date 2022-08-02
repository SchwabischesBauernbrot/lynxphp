<?php

// we may want to move sphinx over redis to reduce the number of requirements
// iff we find sphinx to be an adequate replacement

/*
if (class_exists('Redis')) {
  include 'redis.php';
  class auto_scratch_driver extends redis_scratch_driver {
  }
  return;
}
*/
/*
// deprecated until further testing
} elseif (class_exists('APCIterator')) {
  class memoryhash extends apcStub {
  }
} elseif (function_exists('mysqli_connect')) {
  global $CONFIG;
  // this is WAY slower than redis (but still pretty compact?)
  // maybe like 100x to 1000x slower
  if (isset($CONFIG['sphinx'])) {
    class memoryhash extends sphinxStub {
    }
  } else {
    class memoryhash extends mysqlStub {
    }
  }
} elseif (function_exists('shm_attach')) {
  class memoryhash extends sharedmemStub {
  }
*/

include 'file2.php';
class auto_scratch_driver extends file2_scratch_driver {
}

?>