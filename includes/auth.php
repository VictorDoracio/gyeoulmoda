<?php
declare(strict_types=1);

/**
 * includes/auth.php
 * Bootstrap de sessão + helpers de autenticação.
 * Incluir no topo de toda página, ANTES de qualquer saída de texto.
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('GYEOULMODA_SESSID');
    session_start();
}

$agora  = time();
$limite = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 1800;
if (!empty($_SESSION['ultimo_acesso']) && ($agora - (int)$_SESSION['ultimo_acesso']) > $limite) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
    flash_set('aviso', 'Sessão expirada por inatividade. Faça login novamente.');
}
$_SESSION['ultimo_acesso'] = $agora;

/** Devolve o usuário logado (array) ou null. */
function usuario_atual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

/** true se há usuário logado. */
function is_logado(): bool
{
    return isset($_SESSION['usuario']['id']);
}

/** true se o usuário logado é admin. */
function is_admin(): bool
{
    return is_logado() && (($_SESSION['usuario']['perfil'] ?? '') === 'admin');
}

/** Registra o usuário na sessão (chamar SOMENTE após password_verify). */
function login_usuario(array $dados): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id'     => (int)$dados['id'],
        'nome'   => (string)$dados['nome'],
        'email'  => (string)$dados['email'],
        'perfil' => (string)$dados['perfil'],
    ];
    $_SESSION['ultimo_acesso'] = time();
}

/** Encerra a sessão do usuário atual. */
function logout_usuario(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
    }
    session_destroy();
}

/** Exige login. Redireciona para /login.php preservando a URL de destino. */
function require_login(): void
{
    if (!is_logado()) {
        $next = $_SERVER['REQUEST_URI'] ?? url('');
        flash_set('aviso', 'Faça login para continuar.');
        redirect('login.php?next=' . urlencode($next));
    }
}

/** Exige perfil admin. Responde 403 para clientes logados. */
function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        exit('Acesso restrito.');
    }
}