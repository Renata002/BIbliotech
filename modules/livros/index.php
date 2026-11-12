
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

// Todos os perfis logados podem consultar os livros
exigirLogin();

// Busca os livros cadastrados no banco
$consulta = $pdo->prepare(
    'SELECT id, titulo, autor, ano_publicacao,
            quantidade_total, quantidade_disponivel
     FROM livros
     ORDER BY titulo'
);

$consulta->execute();

$livros = $consulta->fetchAll(PDO::FETCH_ASSOC);

// Verifica o perfil do usuário
$tipoUsuario = $_SESSION['tipo'];

$podeCadastrar = in_array(
    $tipoUsuario,
    ['admin', 'professor'],
    true
);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livros — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="page-title">

        <h1>Acervo</h1>

        <?php if ($podeCadastrar): ?>
            <a class="button" href="form.php">
                Novo livro
            </a>
        <?php endif; ?>

    </div>

    <div class="card">

        <table>

            <thead>
                <tr>
                    <th>Título</th>
                    <th>Autor</th>
                    <th>Ano</th>
                    <th>Total</th>
                    <th>Disponíveis</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($livros) > 0): ?>

                    <?php foreach ($livros as $livro): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars((string) $livro['ano_publicacao'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= (int) $livro['quantidade_total'] ?>
                            </td>

                            <td>
                                <?= (int) $livro['quantidade_disponivel'] ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">
                            Nenhum livro cadastrado.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
