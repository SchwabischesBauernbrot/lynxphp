<?php

$params = $getHandler();

$accountPortal = getAccountPortal();
wrapContent($accountPortal['header'] . getChangeUserPassForm() . $accountPortal['footer']);

?>