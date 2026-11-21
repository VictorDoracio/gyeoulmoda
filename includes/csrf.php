<?php
declare(strict_types=1);

/**
 * includes/csrf.php
 * CSRF token por sessão.
 */

/** Retorna o token da sessão, gerando-o na primeira chamada. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Retorna o campo <input hidden> já com o token escapado. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Valida o token em requisições POST.
 */
function csrf_verify(): void
{
    if (!is_post()) {
        return;
    }
    $enviado  = $_POST['csrf_token'] ?? '';
    $esperado = $_SESSION['csrf_token'] ?? '';

    if (!is_string($enviado) || $esperado === '' || !hash_equals($esperado, $enviado)) {
        http_response_code(400);
        exit('Requisição inválida (CSRF). Recarregue a página e tente novamente.');
    }
}