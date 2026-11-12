
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado
function exigirLogin(): void
{
    if (
        !isset($_SESSION['usuario_id']) ||
        !isset($_SESSION['tipo'])
    ) {
        header('Location: ../../login.php');
        exit;
    }
}

// Verifica se o perfil possui autorização para acessar a página
function exigirPerfil(array $perfis): void
{
    exigirLogin();

    if (!in_array($_SESSION['tipo'], $perfis, true)) {
        header('Location: ../../acesso_negado.php');
        exit;
    }
}
