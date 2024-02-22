<?php

$params = $getHandler();

$tmpl = generateForm($params['action'], $shared['admin_fields'], array());

wrapContent($tmpl);

?>
