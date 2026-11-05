
<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../database/conexao.php';

exigirPerfil(['admin', 'professor']);

// Inicializar variáveis.
$id = null;

$usuario = [
    'nome' => '',
    'email' => '',
    'tipo' => 'aluno',
    'status' => 'ativo'
];

$mensagem = '';
$tipoMensagem = '';

// Verificar se o formulário foi enviado.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Receber os dados.
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $status = $_POST['status'] ?? '';

    // Manter os dados preenchidos.
    $usuario = [
        'nome' => $nome,
        'email' => $email,
        'tipo' => $tipo,
        'status' => $status
    ];

    // 2. Validar campos obrigatórios.
    if (
        $nome === '' ||
        $email === '' ||
        trim($senha) === '' ||
        $tipo === '' ||
        $status === ''
    ) {

        $mensagem = 'Preencha todos os campos obrigatórios.';
        $tipoMensagem = 'erro';

    // 3. Validar o e-mail.
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $mensagem = 'Informe um endereço de e-mail válido.';
        $tipoMensagem = 'erro';

    // 4. Validar tipo e status.
    } elseif (
        !in_array($tipo, ['aluno', 'professor', 'admin'], true) ||
        !in_array($status, ['ativo', 'bloqueado'], true)
    ) {

        $mensagem = 'Tipo de usuário ou status inválido.';
        $tipoMensagem = 'erro';

    } else {

        try {

            // 5. Verificar e-mail duplicado.
            $stmt = $pdo->prepare(
                'SELECT id FROM usuarios WHERE email = :email'
            );

            $stmt->execute([
                ':email' => $email
            ]);

            if ($stmt->fetch(PDO::FETCH_ASSOC)) {

                $mensagem = 'Este e-mail já está cadastrado.';
                $tipoMensagem = 'erro';

            } else {

                // 6. Proteger a senha.
                $senhaHash = password_hash(
                    $senha,
                    PASSWORD_DEFAULT
                );

                // 7. Inserir usuário no banco.
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

                // 8. Mensagem de sucesso.
                $mensagem = 'Usuário cadastrado com sucesso!';
                $tipoMensagem = 'sucesso';

                // Limpar formulário.
                $usuario = [
                    'nome' => '',
                    'email' => '',
                    'tipo' => 'aluno',
                    'status' => 'ativo'
                ];
            }

        } catch (PDOException $e) {

            // Mostrar erro detalhado durante os testes.
            $mensagem = 'Erro ao cadastrar: ' . $e->getMessage();
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

    <title>Novo usuário</title>

    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>

<main class="container">

    <div class="card">

        <h1>Novo usuário</h1>

        <?php if (!empty($mensagem)): ?>

            <div class="alert">
                <?= htmlspecialchars($mensagem) ?>
            </div>

        <?php endif; ?>

        <form method="POST" action="">

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

            <label for="senha">Senha</label>

            <input
                type="password"
                id="senha"
                name="senha"
                required
            >

            <label for="tipo">Tipo</label>

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

    </div>

</main>

</body>
</html>
