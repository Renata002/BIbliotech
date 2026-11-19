
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

$mensagem = '';
$tipoMensagem = '';

try {

    // Inicia a transação
    $pdo->beginTransaction();

    // Busca o empréstimo e bloqueia o registro
    $consulta = $pdo->prepare(
        'SELECT id, livro_id, status
         FROM emprestimos
         WHERE id = :id
         FOR UPDATE'
    );

    $consulta->execute([
        ':id' => $id
    ]);

    $emprestimo = $consulta->fetch(PDO::FETCH_ASSOC);

    // Verifica se o empréstimo existe
    if (!$emprestimo) {
        throw new Exception('Empréstimo não encontrado.');
    }

    // Verifica se ainda pode ser devolvido
    if (
        $emprestimo['status'] !== 'ativo' &&
        $emprestimo['status'] !== 'atrasado'
    ) {
        throw new Exception(
            'Este empréstimo já foi devolvido ou não permite devolução.'
        );
    }

    // Registra a data real e altera o status
    $atualizarEmprestimo = $pdo->prepare(
        "UPDATE emprestimos
         SET data_devolucao_real = :data_devolucao_real,
             status = 'devolvido'
         WHERE id = :id
           AND status IN ('ativo', 'atrasado')"
    );

    $atualizarEmprestimo->execute([
        ':data_devolucao_real' => date('Y-m-d'),
        ':id' => $id
    ]);

    if ($atualizarEmprestimo->rowCount() !== 1) {
        throw new Exception(
            'Não foi possível atualizar o empréstimo.'
        );
    }

    // Aumenta a quantidade disponível do livro
    $atualizarLivro = $pdo->prepare(
        'UPDATE livros
         SET quantidade_disponivel = quantidade_disponivel + 1
         WHERE id = :livro_id'
    );

    $atualizarLivro->execute([
        ':livro_id' => $emprestimo['livro_id']
    ]);

    if ($atualizarLivro->rowCount() !== 1) {
        throw new Exception(
            'Não foi possível atualizar a quantidade do livro.'
        );
    }

    // Confirma as alterações
    $pdo->commit();

    $mensagem = 'Livro devolvido com sucesso!';
    $tipoMensagem = 'sucesso';

} catch (Throwable $e) {

    // Desfaz as alterações se acontecer algum erro
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $mensagem = $e instanceof PDOException
        ? 'Não foi possível registrar a devolução. Tente novamente.'
        : $e->getMessage();

    $tipoMensagem = 'erro';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Devolução — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="card">

        <h1>Registrar devolução</h1>

        <div class="alert">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <a class="button" href="index.php">
            Voltar para empréstimos
        </a>

    </div>

</main>

</body>
</html>
