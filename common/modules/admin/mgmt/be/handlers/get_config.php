<?php
$params = $get();

// are we logged in as an admin?
sendResponse(array(
  'DB_DRIVER' => DB_DRIVER,
  'DB_HOST' => DB_HOST,
  'DB_USER' => DB_USER,
  'DB_NAME' => DB_NAME,
  'DISABLE_MODULES' => DISABLE_MODULES,
  'SCRATCH_DRIVER' => SCRATCH_DRIVER,
  'QUEUE_DRIVER' => QUEUE_DRIVER,
  'FRONTEND_BASE_URL' => FRONTEND_BASE_URL,
  'BACKEND_HEAD_SUPPORT' => BACKEND_HEAD_SUPPORT,
));
?>