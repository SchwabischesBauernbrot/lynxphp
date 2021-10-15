<?php

// db check...
global $db;
sendResponse(array('check' => ($db->conn !== null) ? 'ok' : 'not ok'));
