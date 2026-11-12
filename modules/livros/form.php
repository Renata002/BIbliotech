
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

// Somente administrador e professor podem cadastrar livros
exigirPerfil(['admin', 'professor']);

$livro = [
    'titulo' => '',
    'autor' => '',
    'ano_publicacao' => '',
    'quantidade_total' => 1
];

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recebe os dados do formulário
    $livro['titulo'] = trim($_POST['titulo'] ?? '');
    $livro['autor'] = trim($_POST['autor'] ?? '');
    $livro['ano_publicacao'] = trim($_POST['ano_publicacao'] ?? '');
    $livro['quantidade_total'] = trim($_POST['quantidade_total'] ?? '');

    // Valida os campos obrigatórios
    if (
        $livro['titulo'] === '' ||
        $livro['autor'] === '' ||
        $livro['ano_publicacao'] === '' ||
        $livro['quantidade_total'] === ''
    ) {

        $mensagem = 'Preencha todos os campos.';
        $tipoMensagem = 'erro';

    } elseif (
        filter_var($livro['ano_publicacao'], FILTER_VALIDATE_INT) === false ||
        (int) $livro['ano_publicacao'] < 1 ||
        (int) $livro['ano_publicacao'] > (int) date('Y')
    ) {

        $mensagem = 'Informe um ano de publicação válido.';
        $tipoMensagem = 'erro';

    } elseif (
        filter_var($livro['quantidade_total'], FILTER_VALIDATE_INT) === false ||
        (int) $livro['quantidade_total'] < 1
    ) {

        $mensagem = 'A quantidade total deve ser um número inteiro maior que zero.';
        $tipoMensagem = 'erro';

    } else {

        $quantidadeTotal = (int) $livro['quantidade_total'];

        // A quantidade disponível começa igual à quantidade total
        $quantidadeDisponivel = $quantidadeTotal;

        try {

            // Salva o livro no banco de dados
            $sql = 'INSERT INTO livros
                    (titulo, autor, ano_publicacao, quantidade_total, quantidade_disponivel)
                    VALUES
                    (:titulo, :autor, :ano_publicacao, :quantidade_total, :quantidade_disponivel)';

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':titulo' => $livro['titulo'],
                ':autor' => $livro['autor'],
                ':ano_publicacao' => (int) $livro['ano_publicacao'],
                ':quantidade_total' => $quantidadeTotal,
                ':quantidade_disponivel' => $quantidadeDisponivel
            ]);

            $mensagem = 'Livro cadastrado com sucesso!';
            $tipoMensagem = 'sucesso';

            // Limpa o formulário após o cadastro
            $livro = [
                'titulo' => '',
                'autor' => '',
                'ano_publicacao' => '',
                'quantidade_total' => 1
            ];

        } catch (PDOException $e) {

            $mensagem = 'Não foi possível cadastrar o livro. Verifique os dados e tente novamente.';
            $tipoMensagem = 'erro';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo livro — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="card">

        <h1>Novo livro</h1>

        <?php if ($mensagem !== ''): ?>

            <div class="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <form method="POST">

            <label for="titulo">Título</label>
            <input
                type="text"
                id="titulo"
                name="titulo"
                value="<?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="autor">Autor</label>
            <input
                type="text"
                id="autor"
                name="autor"
                value="<?= htmlspecialchars($livro['autor'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="ano_publicacao">Ano de publicação</label>
            <input
                type="number"
                id="ano_publicacao"
                name="ano_publicacao"
                min="1"
                max="<?= date('Y') ?>"
                value="<?= htmlspecialchars((string) $livro['ano_publicacao'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <label for="quantidade_total">Quantidade total</label>
            <input
                type="number"
                id="quantidade_total"
                name="quantidade_total"
                min="1"
                value="<?= htmlspecialchars((string) $livro['quantidade_total'], ENT_QUOTES, 'UTF-8') ?>"
                required
            >

            <button type="submit">Salvar livro</button>

        </form>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
