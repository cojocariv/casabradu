<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
crm_logout();
header('Location: ' . crm_url('login.php'));
exit;
