<?php

$params = $getModule();

/*
  $io = array(
    'uri' => $r['board'],
    'threadid' => $r['threadid'],
    'postid' => $r['postid'],
    'post' => $post,
    'posts_model' => $posts_model,
    'password' => $password,
    'allowDelete' => $allowDelete,
  );
*/

$posts_priv_model = getPrivatePostsModel($boardUri);
global $db;
$post_priv = $db->findById($posts_priv_model, $io['postid']);
if ($post_priv['password'] && $post_priv['password'] === md5(BACKEND_KEY . $io['password'])) {
  $io['allowDelete'] = true;
}
$io['log'][] = array('passcheck' => true, 'db' => $post_priv['password'], 'passed' => $io['password'], 'salted' => md5(BACKEND_KEY . $io['password']));
