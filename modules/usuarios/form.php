
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

$tipoUsuario = $_SESSION['tipo'];

// Receber o ID pela URL ou pelo formulário.
$id = null;
$erroId = false;

$idRecebido = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? ($_POST['id'] ?? '')
    : ($_GET['id'] ?? '');

if ($idRecebido !== '') {
    $id = filter_var($idRecebido, FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        $id = null;
        $erroId = true;
    }
}

$usuario = [
    'nome' => '',
    'email' => '',
    'tipo' => 'aluno',
    'status' => 'ativo'
];

$mensagem = '';
$tipoMensagem = '';
$usuarioExiste = false;
$acessoNegado = false;

// Verificar se o ID recebido é inválido.
if ($erroId) {
    $mensagem = 'ID de usuário inválido.';
    $tipoMensagem = 'erro';
}

// Localizar usuário quando houver ID.
if (!$erroId && $id !== null) {
    try {
        $stmt = $pdo->prepare(
            'SELECT id, nome, email, tipo, status
             FROM usuarios
             WHERE id = :id'
        );

        $stmt->execute([':id' => $id]);

        $dadosUsuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($dadosUsuario) {

            // Professor só pode acessar usuários do tipo aluno.
            if (
                $tipoUsuario === 'professor' &&
                $dadosUsuario['tipo'] !== 'aluno'
            ) {
                $acessoNegado = true;
                $mensagem = 'Você não tem permissão para editar este usuário.';
                $tipoMensagem = 'erro';

            } else {
                $usuario = $dadosUsuario;
                $usuarioExiste = true;
            }

        } else {
            $mensagem = 'Usuário não encontrado.';
            $tipoMensagem = 'erro';
        }

    } catch (PDOException $e) {
        $mensagem = 'Erro ao localizar o usuário.';
        $tipoMensagem = 'erro';
    }
}

// Processar o formulário.
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !$erroId &&
    !$acessoNegado
) {

    if ($id !== null && !$usuarioExiste) {

        $mensagem = 'Não foi possível localizar o usuário.';
        $tipoMensagem = 'erro';

    } else {

        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $status = $_POST['status'] ?? '';

        // Professor só pode cadastrar ou manter usuários como alunos.
        if ($tipoUsuario === 'professor') {
            $tipo = 'aluno';
        } else {
            $tipo = $_POST['tipo'] ?? '';
        }

        $usuario = [
            'nome' => $nome,
            'email' => $email,
            'tipo' => $tipo,
            'status' => $status
        ];

        // Validar campos obrigatórios.
        if (
            $nome === '' ||
            $email === '' ||
            $tipo === '' ||
            $status === '' ||
            ($id === null && trim($senha) === '')
        ) {

            $mensagem = 'Preencha todos os campos obrigatórios.';
            $tipoMensagem = 'erro';

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

            $mensagem = 'Informe um e-mail válido.';
            $tipoMensagem = 'erro';

        } elseif (
            !in_array($tipo, ['aluno', 'professor', 'admin'], true) ||
            !in_array($status, ['ativo', 'bloqueado'], true)
        ) {

            $mensagem = 'Tipo de usuário ou status inválido.';
            $tipoMensagem = 'erro';

        } else {

            try {

                // Verificar se o e-mail já está cadastrado.
                if ($id !== null) {

                    $stmt = $pdo->prepare(
                        'SELECT id FROM usuarios
                         WHERE email = :email
                         AND id <> :id'
                    );

                    $stmt->execute([
                        ':email' => $email,
                        ':id' => $id
                    ]);

                } else {

                    $stmt = $pdo->prepare(
                        'SELECT id FROM usuarios
                         WHERE email = :email'
                    );

                    $stmt->execute([
                        ':email' => $email
                    ]);
                }

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {

                    $mensagem = 'Este e-mail já está cadastrado por outro usuário.';
                    $tipoMensagem = 'erro';

                } else {

                    // EDITAR USUÁRIO.
                    if ($id !== null) {

                        if (trim($senha) !== '') {

                            $senhaHash = password_hash(
                                $senha,
                                PASSWORD_DEFAULT
                            );

                            $stmt = $pdo->prepare(
                                'UPDATE usuarios
                                 SET nome = :nome,
                                     email = :email,
                                     senha = :senha,
                                     tipo = :tipo,
                                     status = :status
                                 WHERE id = :id'
                            );

                            $stmt->execute([
                                ':nome' => $nome,
                                ':email' => $email,
                                ':senha' => $senhaHash,
                                ':tipo' => $tipo,
                                ':status' => $status,
                                ':id' => $id
                            ]);

                        } else {

                            $stmt = $pdo->prepare(
                                'UPDATE usuarios
                                 SET nome = :nome,
                                     email = :email,
                                     tipo = :tipo,
                                     status = :status
                                 WHERE id = :id'
                            );

                            $stmt->execute([
                                ':nome' => $nome,
                                ':email' => $email,
                                ':tipo' => $tipo,
                                ':status' => $status,
                                ':id' => $id
                            ]);
                        }

                        $mensagem = 'Usuário atualizado com sucesso!';
                        $tipoMensagem = 'sucesso';

                        $usuarioExiste = true;

                    } else {

                        // CADASTRAR NOVO USUÁRIO.
                        $senhaHash = password_hash(
                            $senha,
                            PASSWORD_DEFAULT
                        );

                        $stmt = $pdo->prepare(
                            'INSERT INTO usuarios
                             (nome, email, senha, tipo, status)
                             VALUES
                             (:nome, :email, :senha, :tipo, :status)'
                        );

                        $stmt->execute([
                            ':nome' => $nome,
                            ':email' => $email,
                            ':senha' => $senhaHash,
                            ':tipo' => $tipo,
                            ':status' => $status
                        ]);

                        $mensagem = 'Usuário cadastrado com sucesso!';
                        $tipoMensagem = 'sucesso';

                        $usuario = [
                            'nome' => '',
                            'email' => '',
                            'tipo' => 'aluno',
                            'status' => 'ativo'
                        ];
                    }
                }

            } catch (PDOException $e) {

                $mensagem = 'Erro ao salvar usuário. Verifique os dados e tente novamente.';
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

    <title><?= $id !== null ? 'Editar usuário' : 'Novo usuário' ?></title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="card">

        <h1>
            <?= $id !== null ? 'Editar usuário' : 'Novo usuário' ?>
        </h1>

        <?php if ($mensagem !== ''): ?>
            <div class="alert">
                <?= htmlspecialchars($mensagem) ?>
            </div>
        <?php endif; ?>

        <?php if (!$erroId && !$acessoNegado && ($id === null || $usuarioExiste)): ?>

            <form method="POST" action="">

                <?php if ($id !== null): ?>
                    <input
                        type="hidden"
                        name="id"
                        value="<?= htmlspecialchars((string)$id) ?>"
                    >
                <?php endif; ?>

                <label for="nome">Nome</label>

                <input
                    type="text"
                    id="nome"
                    name="nome"
                    value="<?= htmlspecialchars($usuario['nome']) ?>"
                    required
                >

                <label for="email">E-mail</label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($usuario['email']) ?>"
                    required
                >

                <label for="senha">
                    Senha
                    <?= $id !== null ? '(deixe vazia para manter a atual)' : '' ?>
                </label>

                <input
                    type="password"
                    id="senha"
                    name="senha"
                    <?= $id === null ? 'required' : '' ?>
                >

                <label for="tipo">Tipo</label>

                <?php if ($tipoUsuario === 'professor'): ?>

                    <input type="hidden" name="tipo" value="aluno">
                    <input type="text" value="Aluno" disabled>

                <?php else: ?>

                    <select name="tipo" id="tipo" required>

                        <option value="aluno"
                            <?= $usuario['tipo'] === 'aluno' ? 'selected' : '' ?>>
                            Aluno
                        </option>

                        <option value="professor"
                            <?= $usuario['tipo'] === 'professor' ? 'selected' : '' ?>>
                            Professor
                        </option>

                        <option value="admin"
                            <?= $usuario['tipo'] === 'admin' ? 'selected' : '' ?>>
                            Administrador
                        </option>

                    </select>

                <?php endif; ?>

                <label for="status">Status</label>

                <select name="status" id="status" required>

                    <option value="ativo"
                        <?= $usuario['status'] === 'ativo' ? 'selected' : '' ?>>
                        Ativo
                    </option>

                    <option value="bloqueado"
                        <?= $usuario['status'] === 'bloqueado' ? 'selected' : '' ?>>
                        Bloqueado
                    </option>

                </select>

                <button type="submit">Salvar</button>

            </form>

        <?php endif; ?>

    </div>

</main>

</body>
</html>
