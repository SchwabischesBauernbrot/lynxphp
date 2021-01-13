<?php

function renderPostActions($boardUri, $options = false) {
  $templates = loadTemplates('mixins/post_actions');
  return $templates['header'];
}

?>
