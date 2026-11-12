
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirLogin();

$tipoUsuario = $_SESSION['tipo'];

// Conta os livros cadastrados
$livros = $pdo->query(
    'SELECT COUNT(*) FROM livros'
)->fetchColumn();

// Conta os usuários conforme o perfil
$usuarios = 0;

if ($tipoUsuario === 'admin') {

    // Administrador: conta todos os usuários
    $usuarios = $pdo->query(
        'SELECT COUNT(*) FROM usuarios'
    )->fetchColumn();

} elseif ($tipoUsuario === 'professor') {

    // Professor: conta somente os alunos
    $consulta = $pdo->prepare(
        'SELECT COUNT(*) FROM usuarios WHERE tipo = :tipo'
    );

    $consulta->execute([
        ':tipo' => 'aluno'
    ]);

    $usuarios = $consulta->fetchColumn();
}

// Conta os empréstimos ativos
$emprestimos = $pdo->query(
    "SELECT COUNT(*) FROM emprestimos WHERE status = 'ativo'"
)->fetchColumn();

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <h1>
        Olá, <?= htmlspecialchars($_SESSION['nome']) ?>!
    </h1>

    <p>
        Perfil:
        <strong><?= htmlspecialchars($tipoUsuario) ?></strong>
    </p>

    <section class="grid">

        <div class="card">
            <h2>📚 Livros</h2>
            <strong><?= (int) $livros ?></strong>
        </div>

        <?php if (in_array($tipoUsuario, ['admin', 'professor'], true)): ?>

            <div class="card">
                <h2>👥 Usuários</h2>
                <strong><?= (int) $usuarios ?></strong>
            </div>

        <?php endif; ?>

        <div class="card">
            <h2>📋 Empréstimos ativos</h2>
            <strong><?= (int) $emprestimos ?></strong>
        </div>

    </section>

    <div class="card">
        <h2>Próximas tarefas</h2>

        <ul>
            <li>Completar CRUD de usuários.</li>
            <li>Completar CRUD de livros.</li>
            <li>Implementar regras de empréstimo e devolução.</li>
            <li>Testar permissões dos três perfis.</li>
        </ul>
    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
