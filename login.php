<?php
require_once 'db.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM pap_utilizador WHERE EMAIL = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Verificação direta em texto limpo
        if ($user && $password === $user['PASSWORD']) {
            $_SESSION['user_id'] = $user['IDU'];
            $_SESSION['user_nome'] = $user['NOME'];
            $_SESSION['user_tipo'] = $user['TIPO'];

            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "E-mail ou palavra-passe incorretos.";
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticação | PAP Manager</title>
    
    <!-- Tipografia Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Ícones Phosphor Icons -->
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

        /* Contentor Principal Split Screen */
        .login-container {
            display: flex;
            width: 100%;
            height: 100vh;
        }

        /* Lado Esquerdo: Formulário de Login */
        .login-left {
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

        .login-box {
            width: 100%;
            max-width: 360px;
        }

        .brand {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 32px;
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
            margin-bottom: 32px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
        }

        input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
            background: #f8fafc;
        }

        input:focus {
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
            margin-top: 28px;
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

        /* Lado Direito: Imagem de Capa dentro da pasta imagens/ */
        .login-right {
            flex: 1;
            background-image: url('imagens/lgn.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        /* Responsividade para ecrãs mais pequenos / telemóveis */
        @media (max-width: 900px) {
            .login-right {
                display: none;
            }
            .login-left {
                width: 100%;
                min-width: auto;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>

    <div class="login-container">
        
        <!-- LADO ESQUERDO: FORMULÁRIO -->
        <div class="login-left">
            <div class="login-box">
                
                <div class="brand">
                    <i class="ph-fill ph-kanban"></i>
                    PAP Manager
                </div>
                
                <h1>Iniciar Sessão</h1>
                <p class="subtitle">Introduza as suas credenciais para aceder</p>

                <?php if (!empty($erro)): ?>
                    <div class="alert"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <label>E-mail</label>
                        <input type="email" name="email" placeholder="nome@escola.pt" required>
                    </div>

                    <div class="input-group">
                        <label>Palavra-passe</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>

                    <button type="submit">Entrar no Painel</button>
                </form>

                <div class="footer-link">
                    Ainda não tem conta? <a href="registo_user.php">Registar conta</a>
                </div>

            </div>
        </div>

        <!-- LADO DIREITO: IMAGEM (imagens/lgn.png) -->
        <div class="login-right"></div>

    </div>

</body>
</html>