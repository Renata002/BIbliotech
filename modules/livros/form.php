
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$livro = [
    'titulo' => '',
    'autor' => '',
    'ano_publicacao' => '',
    'quantidade_total' => 1,
    'quantidade_disponivel' => 1
];

$mensagem = '';
$tipoMensagem = '';

$id = null;
$modoEdicao = false;
$livroEncontrado = true;

// Recebe o ID pela URL ou pelo formulário
$idRecebido = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['id'] ?? '')
    : ($_GET['id'] ?? '');

if ($idRecebido !== '') {

    $idValidado = filter_var($idRecebido, FILTER_VALIDATE_INT);

    if ($idValidado === false || $idValidado < 1) {
        $mensagem = 'ID do livro inválido.';
        $tipoMensagem = 'erro';
        $livroEncontrado = false;
    } else {
        $id = (int) $idValidado;
        $modoEdicao = true;

        // Verifica se o livro existe
        $consulta = $pdo->prepare(
            'SELECT id, titulo, autor, ano_publicacao,
                    quantidade_total, quantidade_disponivel
             FROM livros
             WHERE id = :id'
        );

        $consulta->execute([':id' => $id]);

        $livroBanco = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$livroBanco) {
            $mensagem = 'Livro não encontrado.';
            $tipoMensagem = 'erro';
            $livroEncontrado = false;
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $livro = $livroBanco;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $livroEncontrado) {

    // Recebe os dados enviados pelo formulário
    $livro['titulo'] = trim($_POST['titulo'] ?? '');
    $livro['autor'] = trim($_POST['autor'] ?? '');
    $livro['ano_publicacao'] = trim($_POST['ano_publicacao'] ?? '');
    $livro['quantidade_total'] = trim($_POST['quantidade_total'] ?? '');

    if ($modoEdicao) {
        $livro['quantidade_disponivel'] = trim(
            $_POST['quantidade_disponivel'] ?? ''
        );
    }

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

        // No cadastro, a quantidade disponível começa igual à total
        if (!$modoEdicao) {
            $quantidadeDisponivel = $quantidadeTotal;

        } else {

            // Na edição, recebe a quantidade disponível informada
            if (
                filter_var(
                    $livro['quantidade_disponivel'],
                    FILTER_VALIDATE_INT
                ) === false
            ) {
                $mensagem = 'A quantidade disponível deve ser um número inteiro.';
                $tipoMensagem = 'erro';

            } else {
                $quantidadeDisponivel = (int) $livro['quantidade_disponivel'];

                // Regra obrigatória da Aula 08
                if (
                    $quantidadeDisponivel < 0 ||
                    $quantidadeDisponivel > $quantidadeTotal
                ) {
                    $mensagem = 'A quantidade disponível deve estar entre zero e a quantidade total.';
                    $tipoMensagem = 'erro';
                }
            }
        }

        if ($mensagem === '') {

            try {

                if ($modoEdicao) {

                    // Atualiza o livro existente
                    $sql = 'UPDATE livros
                            SET titulo = :titulo,
                                autor = :autor,
                                ano_publicacao = :ano_publicacao,
                                quantidade_total = :quantidade_total,
                                quantidade_disponivel = :quantidade_disponivel
                            WHERE id = :id';

                    $stmt = $pdo->prepare($sql);

                    $stmt->execute([
                        ':titulo' => $livro['titulo'],
                        ':autor' => $livro['autor'],
                        ':ano_publicacao' => (int) $livro['ano_publicacao'],
                        ':quantidade_total' => $quantidadeTotal,
                        ':quantidade_disponivel' => $quantidadeDisponivel,
                        ':id' => $id
                    ]);

                    $mensagem = 'Livro atualizado com sucesso!';
                    $tipoMensagem = 'sucesso';

                } else {

                    // Cadastra um livro novo
                    $sql = 'INSERT INTO livros
                            (titulo, autor, ano_publicacao,
                             quantidade_total, quantidade_disponivel)
                            VALUES
                            (:titulo, :autor, :ano_publicacao,
                             :quantidade_total, :quantidade_disponivel)';

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

                    // Limpa os campos após o cadastro
                    $livro = [
                        'titulo' => '',
                        'autor' => '',
                        'ano_publicacao' => '',
                        'quantidade_total' => 1,
                        'quantidade_disponivel' => 1
                    ];
                }

            } catch (PDOException $e) {
                $mensagem = 'Não foi possível salvar o livro. Tente novamente.';
                $tipoMensagem = 'erro';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $modoEdicao ? 'Editar livro' : 'Novo livro' ?> — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="card">

        <h1><?= $modoEdicao ? 'Editar livro' : 'Novo livro' ?></h1>

        <?php if ($mensagem !== ''): ?>
            <div class="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($livroEncontrado): ?>

            <form method="POST">

                <?php if ($modoEdicao): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

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

                <?php if ($modoEdicao): ?>

                    <label for="quantidade_disponivel">Quantidade disponível</label>
                    <input
                        type="number"
                        id="quantidade_disponivel"
                        name="quantidade_disponivel"
                        min="0"
                        max="<?= (int) $livro['quantidade_total'] ?>"
                        value="<?= htmlspecialchars((string) $livro['quantidade_disponivel'], ENT_QUOTES, 'UTF-8') ?>"
                        required
                    >

                <?php else: ?>

                    <p>A quantidade disponível será iniciada igual à quantidade total.</p>

                <?php endif; ?>

                <button type="submit">
                    <?= $modoEdicao ? 'Salvar alterações' : 'Salvar livro' ?>
                </button>

            </form>

        <?php endif; ?>

        <p>
            <a href="index.php">Voltar para o acervo</a>
        </p>

    </div>

</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

</body>
</html>
