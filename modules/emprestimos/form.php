
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$mensagem = '';
$tipoMensagem = '';

$dados = [
    'usuario_id' => '',
    'livro_id' => '',
    'data_devolucao_prevista' => ''
];

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Recebe os dados
    $dados['usuario_id'] = $_POST['usuario_id'] ?? '';
    $dados['livro_id'] = $_POST['livro_id'] ?? '';
    $dados['data_devolucao_prevista'] = trim(
        $_POST['data_devolucao_prevista'] ?? ''
    );

    // Valida os IDs
    $usuarioId = filter_var(
        $dados['usuario_id'],
        FILTER_VALIDATE_INT
    );

    $livroId = filter_var(
        $dados['livro_id'],
        FILTER_VALIDATE_INT
    );

    // Valida a data
    $dataDevolucao = DateTime::createFromFormat(
        '!Y-m-d',
        $dados['data_devolucao_prevista']
    );

    $dataValida = $dataDevolucao !== false &&
        $dataDevolucao->format('Y-m-d') === $dados['data_devolucao_prevista'];

    if (
        $usuarioId === false ||
        $usuarioId < 1 ||
        $livroId === false ||
        $livroId < 1 ||
        !$dataValida
    ) {

        $mensagem = 'Informe um aluno, um livro e uma data de devolução válidos.';
        $tipoMensagem = 'erro';

    } else {

        try {

            // Inicia a transação
            $pdo->beginTransaction();

            // Verifica se o aluno existe e está ativo
            $consultaUsuario = $pdo->prepare(
                "SELECT id
                 FROM usuarios
                 WHERE id = :id
                   AND tipo = 'aluno'
                   AND status = 'ativo'"
            );

            $consultaUsuario->execute([
                ':id' => $usuarioId
            ]);

            $usuario = $consultaUsuario->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                throw new Exception(
                    'Aluno não encontrado ou não está ativo.'
                );
            }

            // Consulta e bloqueia o livro durante a transação
            $consultaLivro = $pdo->prepare(
                'SELECT id, quantidade_disponivel
                 FROM livros
                 WHERE id = :id
                 FOR UPDATE'
            );

            $consultaLivro->execute([
                ':id' => $livroId
            ]);

            $livro = $consultaLivro->fetch(PDO::FETCH_ASSOC);

            if (!$livro) {
                throw new Exception('Livro não encontrado.');
            }

            // Verifica se existe quantidade disponível
            if ((int) $livro['quantidade_disponivel'] <= 0) {
                throw new Exception(
                    'Não há exemplares disponíveis para empréstimo.'
                );
            }

            // Registra o empréstimo
            $sqlEmprestimo = 'INSERT INTO emprestimos
                (usuario_id, livro_id, data_retirada,
                 data_devolucao_prevista, status)
                VALUES
                (:usuario_id, :livro_id, :data_retirada,
                 :data_devolucao_prevista, :status)';

            $stmtEmprestimo = $pdo->prepare($sqlEmprestimo);

            $stmtEmprestimo->execute([
                ':usuario_id' => $usuarioId,
                ':livro_id' => $livroId,
                ':data_retirada' => date('Y-m-d'),
                ':data_devolucao_prevista' => $dados['data_devolucao_prevista'],
                ':status' => 'ativo'
            ]);

            // Diminui a quantidade disponível do livro
            $sqlLivro = 'UPDATE livros
                         SET quantidade_disponivel = quantidade_disponivel - 1
                         WHERE id = :id
                           AND quantidade_disponivel > 0';

            $stmtLivro = $pdo->prepare($sqlLivro);

            $stmtLivro->execute([
                ':id' => $livroId
            ]);

            if ($stmtLivro->rowCount() !== 1) {
                throw new Exception(
                    'Não foi possível atualizar a quantidade disponível.'
                );
            }

            // Confirma as duas operações
            $pdo->commit();

            $mensagem = 'Empréstimo registrado com sucesso!';
            $tipoMensagem = 'sucesso';

            // Limpa o formulário
            $dados = [
                'usuario_id' => '',
                'livro_id' => '',
                'data_devolucao_prevista' => ''
            ];

        } catch (Throwable $e) {

            // Desfaz as operações em caso de erro
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $mensagem = $e instanceof PDOException
                ? 'Não foi possível registrar o empréstimo. Verifique os dados e tente novamente.'
                : $e->getMessage();

            $tipoMensagem = 'erro';
        }
    }
}

// Carrega os alunos ativos
$consultaUsuarios = $pdo->prepare(
    "SELECT id, nome
     FROM usuarios
     WHERE tipo = 'aluno'
       AND status = 'ativo'
     ORDER BY nome"
);

$consultaUsuarios->execute();

$usuarios = $consultaUsuarios->fetchAll(PDO::FETCH_ASSOC);

// Carrega somente os livros disponíveis
$consultaLivros = $pdo->prepare(
    'SELECT id, titulo, quantidade_disponivel
     FROM livros
     WHERE quantidade_disponivel > 0
     ORDER BY titulo'
);

$consultaLivros->execute();

$livros = $consultaLivros->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo empréstimo — BiblioTech</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="card">

        <h1>Novo empréstimo</h1>

        <?php if ($mensagem !== ''): ?>

            <div class="alert">
                <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
            </div>

        <?php endif; ?>

        <?php if (count($usuarios) > 0 && count($livros) > 0): ?>

            <form method="POST">

                <label for="usuario_id">Aluno</label>

                <select name="usuario_id" id="usuario_id" required>

                    <option value="">Selecione um aluno</option>

                    <?php foreach ($usuarios as $usuario): ?>

                        <option
                            value="<?= (int) $usuario['id'] ?>"
                            <?= (string) $dados['usuario_id'] === (string) $usuario['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($usuario['nome'], ENT_QUOTES, 'UTF-8') ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <label for="livro_id">Livro</label>

                <select name="livro_id" id="livro_id" required>

                    <option value="">Selecione um livro</option>

                    <?php foreach ($livros as $livro): ?>

                        <option
                            value="<?= (int) $livro['id'] ?>"
                            <?= (string) $dados['livro_id'] === (string) $livro['id'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($livro['titulo'], ENT_QUOTES, 'UTF-8') ?>
                            — <?= (int) $livro['quantidade_disponivel'] ?> disponível(is)
                        </option>

                    <?php endforeach; ?>

                </select>

                <label for="data_devolucao_prevista">
                    Data prevista para devolução
                </label>

                <input
                    type="date"
                    id="data_devolucao_prevista"
                    name="data_devolucao_prevista"
                    value="<?= htmlspecialchars($dados['data_devolucao_prevista'], ENT_QUOTES, 'UTF-8') ?>"
                    required
                >

                <button type="submit">
                    Registrar empréstimo
                </button>

            </form>

        <?php elseif (count($usuarios) === 0): ?>

            <p>Não existem alunos ativos cadastrados.</p>

        <?php else: ?>

            <p>Não existem livros disponíveis para empréstimo.</p>

        <?php endif; ?>

        <p>
            <a href="index.php">Voltar</a>
        </p>

    </div>

</main>

</body>
</html>
