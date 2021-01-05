<?php

// set up frontend specific code (handlers, forms, modules)

// $this is the package
$fePkg = $this->makeFrontend();

// add [users] to admin nav
$fePkg->addModule(PIPELINE_ADMIN_NAV, 'nav');

$fePkg->addHandler('GET', '/admin/users', 'list');
$fePkg->addForm('/admin/users/add', 'add');
$fePkg->addForm('/admin/users/:id/delete', 'delete');

?>