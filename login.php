<?php
require_once 'db.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $tipo     = $_POST['tipo'] ?? 'ALUNO';

    if (!empty($nome) && !empty($email) && !empty($password)) {
        try {
            // Verifica se o e-mail já existe na base de dados
            $stmt = $pdo->prepare("SELECT * FROM pap_utilizador WHERE LOWER(email) = LOWER(?)");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $erro = "Este e-mail já está registado.";
            } else {
                // Inserção em texto limpo (sem encriptação/hash)
                $stmt = $pdo->prepare("INSERT INTO pap_utilizador (nome, email, password, tipo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $password, $tipo]);

                $sucesso = "Conta criada com sucesso! Já pode fazer login.";
            }
        } catch (\PDOException $e) {
            $erro = "Erro ao criar conta: " . $e->getMessage();
        }
    } else {
        $erro = "Por favor, preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo | PAP Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --bg: #ffffff;
            --text: #0f172a;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            min-height: 100vh;
            background-color: #f8fafc;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .register-card {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
        }

        .brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 24px;
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .brand i {
            color: var(--primary);
            font-size: 1.8rem;
        }

        h1 {
            font-size: 1.4rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 6px;
        }

        p.subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 24px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input, select {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.9rem;
            outline: none;
            background: #f8fafc;
            transition: all 0.2s ease;
        }

        input:focus, select:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.2s;
        }

        button:hover {
            background: var(--primary-hover);
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            text-align: center;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 16px;
            text-align: center;
        }

        .footer-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.875rem;
            color: #64748b;
        }

        .footer-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="register-card">
        <div class="brand">
            <i class="ph-fill ph-kanban"></i>
            PAP Manager
        </div>

        <h1>Criar Conta</h1>
        <p class="subtitle">Preencha os dados para efetuar o registo</p>

        <?php if (!empty($erro)): ?>
            <div class="alert-error"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>Nome Completo</label>
                <input type="text" name="nome" placeholder="O teu nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>E-mail</label>
                <input type="email" name="email" placeholder="nome@escola.pt" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <label>Palavra-passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>

            <div class="input-group">
                <label>Tipo de Utilizador</label>
                <select name="tipo">
                    <option value="ALUNO">Aluno</option>
                    <option value="PROFESSOR">Professor</option>
                </select>
            </div>

            <button type="submit">Criar Conta</button>
        </form>

        <div class="footer-link">
            Já tem conta? <a href="login.php">Iniciar sessão</a>
        </div>
    </div>

</body>
</html>
