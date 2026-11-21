<?php
/**
 * database/seed_usuarios.php
 * Executar UMA vez, após importar schema.sql.
 * Uso:
 *   Navegador: http://localhost/gyeoulmoda/database/seed_usuarios.php
 *   CLI:       php database/seed_usuarios.php
 * Depois, recomenda-se APAGAR este arquivo do servidor (segurança).
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

$usuarios = [
    ['admin@gyeoulmoda.local',   'Admin@123',   'admin'],
    ['cliente@gyeoulmoda.local', 'Cliente@123', 'cliente'],
];

$pdo  = db();
$stmt = $pdo->prepare(
    'UPDATE usuarios SET senha_hash = :hash WHERE email = :email AND perfil = :perfil'
);

echo "== seed_usuarios.php ==\n";
foreach ($usuarios as [$email, $senha, $perfil]) {
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $stmt->execute([':hash' => $hash, ':email' => $email, ':perfil' => $perfil]);
    $ok = $stmt->rowCount() === 1 ? 'OK' : 'FALHOU';
    echo "[{$ok}] {$email} (perfil: {$perfil})\n";
}
echo "\nPronto. Senhas gravadas com password_hash(PASSWORD_DEFAULT).\n";