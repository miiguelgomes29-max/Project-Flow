<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAP Manager | Gestão de Projetos</title>
    
    <!-- Tipografia Inter (Google Fonts) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Ícones Profissionais (Phosphor Icons) -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        /* Variáveis de Cor (Estilo SaaS Moderno) */
        :root {
            --primary: #2563eb; /* Azul Tecnológico */
            --primary-hover: #1d4ed8;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-heading: #0f172a;
            --text-body: #475569;
            --border-light: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        /* Reset e Base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-body);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a { text-decoration: none; transition: all 0.2s ease; }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* Navbar */
        header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-light);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 72px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-heading);
            letter-spacing: -0.02em;
        }
        
        .logo i { color: var(--primary); font-size: 1.5rem; }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            font-size: 0.95rem;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-ghost {
            color: var(--text-heading);
        }
        .btn-ghost:hover {
            color: var(--primary);
            background-color: #f1f5f9;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
            box-shadow: var(--shadow-sm);
        }
        .btn-primary:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        /* Hero Section */
        .hero {
            padding: 100px 0;
            background: linear-gradient(to bottom, #ffffff, var(--bg-body));
            border-bottom: 1px solid var(--border-light);
            overflow: hidden;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .hero-text h1 {
            font-size: 3.5rem;
            font-weight: 800;
            color: var(--text-heading);
            line-height: 1.1;
            letter-spacing: -0.03em;
            margin-bottom: 24px;
        }
        
        .hero-text h1 span { color: var(--primary); }

        .hero-text p {
            font-size: 1.125rem;
            margin-bottom: 32px;
            max-width: 480px;
        }

        .hero-buttons {
            display: flex;
            gap: 16px;
        }

        .btn-large {
            padding: 14px 28px;
            font-size: 1rem;
        }

        /* Mockup de Interface CSS (Para aspeto profissional sem imagens) */
        .hero-mockup {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
            transition: transform 0.5s ease;
        }
        
        .hero-mockup:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg);
        }

        .mockup-header {
            background: #f1f5f9;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            gap: 6px;
        }

        .mockup-dot { width: 10px; height: 10px; border-radius: 50%; }
        .dot-red { background: #ef4444; }
        .dot-yellow { background: #eab308; }
        .dot-green { background: #22c55e; }

        .mockup-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .mockup-line { height: 12px; border-radius: 4px; background: #f1f5f9; }
        .line-title { width: 40%; height: 20px; background: #e2e8f0; margin-bottom: 12px; }
        .line-long { width: 100%; }
        .line-medium { width: 75%; }
        .line-short { width: 50%; }

        .mockup-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 16px; }
        .mockup-card { background: #f8fafc; border: 1px solid #e2e8f0; height: 60px; border-radius: 8px; }

        /* Features Section */
        .features {
            padding: 100px 0;
        }

        .section-header {
            text-align: center;
            max-width: 600px;
            margin: 0 auto 64px;
        }

        .section-header h2 {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-heading);
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 32px;
        }

        .feature-card {
            background: var(--bg-card);
            padding: 32px;
            border-radius: 12px;
            border: 1px solid var(--border-light);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
            transform: translateY(-4px);
        }

        .feature-icon-wrapper {
            width: 48px;
            height: 48px;
            background-color: #eff6ff;
            color: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-heading);
            margin-bottom: 12px;
        }

        .feature-card p {
            font-size: 0.95rem;
        }

        /* CTA Section */
        .cta {
            background-color: var(--text-heading);
            color: white;
            padding: 80px 0;
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            margin-bottom: 24px;
            color: white;
        }
        
        .cta p {
            color: #94a3b8;
            margin-bottom: 32px;
            font-size: 1.125rem;
        }

        /* Footer */
        footer {
            background-color: var(--bg-card);
            border-top: 1px solid var(--border-light);
            padding: 32px 0;
            text-align: center;
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Responsividade */
        @media (max-width: 992px) {
            .hero-content { grid-template-columns: 1fr; text-align: center; gap: 40px; }
            .hero-text p { margin: 0 auto 32px; }
            .hero-buttons { justify-content: center; }
            .hero-mockup { transform: none; max-width: 600px; margin: 0 auto; }
        }

        @media (max-width: 768px) {
            .hero-text h1 { font-size: 2.5rem; }
            .navbar { flex-direction: column; height: auto; padding: 16px 0; gap: 16px; }
        }
    </style>
</head>
<body>

    <!-- Navegação -->
    <header>
        <div class="container navbar">
            <div class="logo">
                <i class="ph-fill ph-kanban"></i>
                PAP Manager
            </div>
            <div class="nav-actions">
                <a href="login.php" class="btn btn-ghost">Iniciar Sessão</a>
                <a href="registo_user.php" class="btn btn-primary">Criar Conta Grátis</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1>Eleve a Gestão da sua PAP para o <span>Próximo Nível</span>.</h1>
                <p>A plataforma definitiva para alunos e professores coordenarem tarefas, registarem progressos e trocarem feedback em tempo real.</p>
                <div class="hero-buttons">
                    <a href="registo_user.php" class="btn btn-primary btn-large">Começar Agora</a>
                    <a href="#funcionalidades" class="btn btn-ghost btn-large">Saber Mais &darr;</a>
                </div>
            </div>
            
            <!-- Mockup de Interface Gráfica -->
            <div class="hero-mockup">
                <div class="mockup-header">
                    <div class="mockup-dot dot-red"></div>
                    <div class="mockup-dot dot-yellow"></div>
                    <div class="mockup-dot dot-green"></div>
                </div>
                <div class="mockup-body">
                    <div class="line-title"></div>
                    <div class="mockup-line line-long"></div>
                    <div class="mockup-line line-medium"></div>
                    <div class="mockup-cards">
                        <div class="mockup-card"></div>
                        <div class="mockup-card"></div>
                    </div>
                    <div class="mockup-line line-short" style="margin-top: 10px;"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Funcionalidades -->
    <section id="funcionalidades" class="features container">
        <div class="section-header">
            <h2>Tudo o que precisa num só lugar</h2>
            <p>Esqueça os documentos soltos e os emails perdidos. Centralize toda a documentação e desenvolvimento do seu projeto escolar.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="ph ph-check-square-offset"></i>
                </div>
                <h3>Controlo de Tarefas</h3>
                <p>Estruture o projeto em etapas. Atribua datas limite e controle o estado entre Pendente, Em Progresso e Concluído.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="ph ph-notebook"></i>
                </div>
                <h3>Diário de Bordo</h3>
                <p>Mantenha um registo detalhado das atividades diárias. Fundamental para a elaboração do relatório final da PAP.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="ph ph-link"></i>
                </div>
                <h3>Repositório de Recursos</h3>
                <p>Guarde bibliotecas, links, tutoriais do YouTube e repositórios do GitHub essenciais para o desenvolvimento.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper">
                    <i class="ph ph-chats-circle"></i>
                </div>
                <h3>Feedback Direto</h3>
                <p>O orientador acompanha o processo e deixa anotações construtivas diretamente no painel do projeto do aluno.</p>
            </div>
        </div>
    </section>

    <!-- Call to Action Footer -->
    <section class="cta">
        <div class="container">
            <h2>Pronto para simplificar a sua PAP?</h2>
            <p>Junte-se à plataforma desenhada especificamente para simplificar o sucesso dos alunos e facilitar o trabalho dos professores.</p>
            <a href="registo_user.php" class="btn btn-primary btn-large">Criar a minha conta agora</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; 2026 PAP Manager. Sistema Integrado de Gestão de Projetos Escolares.</p>
        </div>
    </footer>

</body>
</html>