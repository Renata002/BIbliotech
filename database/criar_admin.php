<?php
require_once __DIR__ . '/conexao.php';

$senha = password_hash('123456', PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nome, email, senha, tipo, status)
     VALUES (:nome, :email, :senha, :tipo, :status)'
);

$stmt->execute([
    'nome' => 'Administrador',
    'email' => 'admin@bibliotech.local',
    'senha' => $senha,
    'tipo' => 'admin',
    'status' => 'ativo'
]);

echo 'Administrador criado. Remova este arquivo depois.';
