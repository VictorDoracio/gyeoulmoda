<?php
declare(strict_types=1);

/**
 * includes/helpers.php
 * Utilitários gerais: escape de saída, URL, redirect, flash, old, POST, preço, next.
 */

/** Escapa texto para exibição segura no HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Monta URL absoluta a partir do APP_URL. */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return $path === '' ? APP_URL : APP_URL . '/' . $path;
}

/** Redireciona e encerra a execução. */
function redirect(string $path): never
{
    if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

/** Grava uma mensagem flash para exibição após redirect. */
function flash_set(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

/** Consome e devolve todas as mensagens flash pendentes. */
function flash_get(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Lê um valor antigo de formulário (para repopular após erro de validação). */
function old(string $campo, string $default = ''): string
{
    return $_SESSION['old'][$campo] ?? $default;
}

/** Armazena dados de formulário para repopulação. */
function old_set(array $dados): void
{
    $_SESSION['old'] = $dados;
}

/** Limpa o "old" (chamar após um POST bem-sucedido). */
function old_clear(): void
{
    unset($_SESSION['old']);
}

/** true se a requisição atual é POST. */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** Formata valor monetário no padrão brasileiro (R$ 1.234,56). */
function preco_brl(float|int|string $valor): string
{
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
}

/**
 * Valida um destino de redirecionamento interno (?next=...) contra open redirect.
 */
function next_seguro(?string $next, string $fallback = ''): string
{
    if ($next === null || $next === '') return $fallback;
    $next = trim($next);
    if ($next === '') return $fallback;

    if (str_starts_with($next, '//')) return $fallback;
    if (preg_match('#^[a-z][a-z0-9+.\-]*:#i', $next)) return $fallback;
    if (!str_starts_with($next, '/')) return $fallback;

    $base = rtrim((string)parse_url(APP_URL, PHP_URL_PATH), '/');
    if ($base !== '' && $next !== $base && !str_starts_with($next, $base . '/')) {
        return $fallback;
    }
    return $next;
}