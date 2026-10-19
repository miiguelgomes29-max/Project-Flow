<?php
require_once 'db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $tipo = $_POST['tipo'];

    if (!empty($nome) && !empty($email) && !empty($password) && !empty($tipo)) {
        try {
            // Palavra-passe gravada diretamente em texto limpo
            $stmt = $pdo->prepare("INSERT INTO pap_utilizador (NOME, EMAIL, PASSWORD, TIPO) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $password, $tipo]);
            header("Location: login.php?registado=1");
            exit;
        } catch (PDOException $e) {
            $erro = "O endereço de e-mail já está registado.";
        }
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registo | ProjectFlow</title>
    
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
            height: 100vh;
            width: 100vw;
            background-color: var(--bg);
            color: var(--text);
            overflow: hidden;
        }

        .register-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        .register-left {
            width: 40%;
            min-width: 420px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 48px;
            background: #ffffff;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.05);
            z-index: 2;
            overflow-y: auto;
        }

        .register-box {
            width: 100%;
            max-width: 360px;
        }

        .brand {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 28px;
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
            font-size: 1.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 8px;
            color: var(--text);
        }

        p.subtitle {
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 28px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 6px;
            color: var(--text);
        }

        input, select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        input:focus, select:focus {
            background: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        button {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        button:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 0.875rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .footer-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .footer-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
        }

        .footer-link a:hover {
            text-decoration: underline;
        }

        .register-right {
            flex: 1;
            background-image: url('imagens/lgn.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        @media (max-width: 900px) {
            .register-right {
                display: none;
            }
            .register-left {
                width: 100%;
                min-width: auto;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <div class="register-container">
        
        <div class="register-left">
            <div class="register-box">
                
                <div class="brand">
                    <i class="ph-fill ph-kanban"></i>
                    ProjectFlow
                </div>
                
                <h1>Criar Nova Conta</h1>
                <p class="subtitle">Escolha o seu perfil de acesso</p>

                <?php if (!empty($erro)): ?>
                    <div class="alert"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" placeholder="Ex: João Silva" required>
                    </div>

                    <div class="input-group">
                        <label>E-mail do Aluno/Professor</label>
                        <input type="email" name="email" placeholder="nome@escola.pt" required>
                    </div>

                    <div class="input-group">
                        <label>Palavra-passe</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>

                    <div class="input-group">
                        <label>Tipo de Utilizador</label>
                        <select name="tipo" required>
                            <option value="Aluno">Aluno (Desenvolvedor do Projeto)</option>
                            <option value="Professor">Professor (Orientador)</option>
                        </select>
                    </div>

                    <button type="submit">Criar Conta</button>
                </form>

                <div class="footer-link">
                    Já tem conta? <a href="login.php">Iniciar sessão</a>
                </div>

            </div>
        </div>

        <div class="register-right"></div>

    </div>

</body>
</html>