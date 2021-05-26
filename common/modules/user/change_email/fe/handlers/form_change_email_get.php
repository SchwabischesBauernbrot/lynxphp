<?php

$params = $getHandler();

$accountPortal = getAccountPortal();
wrapContent($accountPortal['header'] . getChangeEmailForm() . $accountPortal['footer']);

?>