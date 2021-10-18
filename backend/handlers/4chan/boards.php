<?php

// https://a.4cdn.org/boards.json
$boards = listBoards();
sendRawResponse($boards);
