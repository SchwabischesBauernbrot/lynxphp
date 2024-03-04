<?php

$params = $getHandler();

$html = '<table>';
$html .= '<tr><th>type<th>key<th>value' . "\n";
$html .= '<tr><td>frontend<td>BASE_HOST<td>' . BASE_HOST . "\n";
$html .= '<tr><td>frontend<td>BACKEND_BASE_URL<td>' . BACKEND_BASE_URL . "\n";
$html .= '<tr><td>frontend<td>BACKEND_PUBLIC_URL<td>' . BACKEND_PUBLIC_URL . "\n";
$html .= '<tr><td>frontend<td>BACKEND_TYPE<td>' . BACKEND_TYPE . "\n";
$html .= '<tr><td>frontend<td>BACKEND_HEAD_SUPPORT<td>' . BACKEND_HEAD_SUPPORT . "\n";
$html .= '<tr><td>frontend<td>BASE_HREF<td>' . BASE_HREF . "\n";
$html .= '<tr><td>frontend<td>SITE_TITLE<td>' . SITE_TITLE . "\n";
$html .= '<tr><td>frontend<td>DEV_MODE<td>' . DEV_MODE . "\n";
$html .= '<tr><td>frontend<td>SCRATCH_DRIVER<td>' . SCRATCH_DRIVER . "\n";
$html .= '<tr><td>frontend<td>FILE_SCRATCH_DIRECTORY<td>' . FILE_SCRATCH_DIRECTORY . "\n";
$html .= '<tr><td>frontend<td>REDIS_HOST<td>' . REDIS_HOST . "\n";
$html .= '<tr><td>frontend<td>REDIS_PORT<td>' . REDIS_PORT . "\n";
$html .= '<tr><td>frontend<td>REDIS_SOCKET<td>' . REDIS_SOCKET . "\n";
$html .= '<tr><td>frontend<td>REDIS_FORCE_HOST<td>' . REDIS_FORCE_HOST . "\n";
$html .= '<tr><td>frontend<td>USER<td>' . USER . "\n";
$html .= '<tr><td>frontend<td>DISABLE_MODULES<td>' . join(', ', DISABLE_MODULES) . "\n";
$html .= '<tr><td>frontend<td>DISABLE_WORK<td>' . DISABLE_WORK . "\n";
$html .= '<tr><td>frontend<td>AUTH_DIRECT<td>' . AUTH_DIRECT . "\n";
$html .= '<tr><td>frontend<td>CANONICAL_BASE<td>' . CANONICAL_BASE . "\n";

$be_config = $pkg->useResource('get_config');
// the host frontend uses might not be the same as how we actually get this
/*
if (file_exists('backend') && is_dir('backend')) {
  $old = getcwd();
  chdir('backend/');
  // SCRATCH_DRIVER already defined...
  include 'config.php';
  chdir($old);
}
*/

if ($be_config) {
  $html .= '<tr><td>backend<td>DB_DRIVER<td>' . $be_config['DB_DRIVER'] . "\n";
  $html .= '<tr><td>backend<td>DB_HOST<td>' . $be_config['DB_HOST'] . "\n";
  $html .= '<tr><td>backend<td>DB_USER<td>' . $be_config['DB_USER'] . "\n";
  $html .= '<tr><td>backend<td>DB_NAME<td>' . $be_config['DB_NAME'] . "\n";
  $html .= '<tr><td>backend<td>DISABLE_MODULES<td>' . join(', ', $be_config['DISABLE_MODULES']) . "\n";
  $html .= '<tr><td>backend<td>SCRATCH_DRIVER<td>' . $be_config['SCRATCH_DRIVER'] . "\n";
  $html .= '<tr><td>backend<td>QUEUE_DRIVER<td>' . $be_config['QUEUE_DRIVER'] . "\n";
  $html .= '<tr><td>backend<td>FRONTEND_BASE_URL<td>' . $be_config['FRONTEND_BASE_URL'] . "\n";
  $html .= '<tr><td>backend<td>BACKEND_HEAD_SUPPORT<td>' . $be_config['BACKEND_HEAD_SUPPORT'] . "\n";
}
$html .= '</table>';


wrapContent($html);