<?php
declare(strict_types=1);

/** logout.php — Encerra a sessão. */

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

logout_usuario();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
flash_set('info', 'Você saiu da sua conta.');
redirect('index.php');