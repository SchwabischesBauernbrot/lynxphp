<?php
chdir('..');
global $_HeaderData;
if (!$_HeaderData) $_HeaderData = wrapContentData($options);
wrapContentFooter($_HeaderData);

?>