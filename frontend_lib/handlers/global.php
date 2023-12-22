<?php

include "../frontend_lib/handlers/mixins/global_portal.php";

function getGlobalPage() {

  // portal header was put here
  $content = <<< EOB
You're an hotpocket, you do it for FREE!
EOB;
  wrapContent(renderGlobalPortal() . $content);
}

?>
