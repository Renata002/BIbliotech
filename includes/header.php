
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$tipoUsuario = $_SESSION['tipo'] ?? '';
?>

<header class="topbar">
    <a class="brand" href="../dashboard/index.php">
        📚 BiblioTech
    </a>

    <nav>
        <a href="../dashboard/index.php">Dashboard</a>

        <?php if ($tipoUsuario === 'admin'): ?>

            <!-- Menu do administrador -->
            <a href="../usuarios/index.php">Usuários</a>
            <a href="../livros/index.php">Gerenciar livros</a>
            <a href="../emprestimos/index.php">Empréstimos</a>

        <?php elseif ($tipoUsuario === 'professor'): ?>

            <!-- Menu do professor -->
            <a href="../usuarios/index.php">Alunos</a>
            <a href="../livros/index.php">Gerenciar livros</a>
            <a href="../emprestimos/index.php">Empréstimos</a>

        <?php elseif ($tipoUsuario === 'aluno'): ?>

            <!-- Menu do aluno -->
            <a href="../livros/index.php">Consultar livros</a>
            <a href="../emprestimos/meus.php">Meus empréstimos</a>

        <?php endif; ?>

        <a href="../../logout.php">Sair</a>
    </nav>
</header>
