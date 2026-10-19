<?php
ob_start();
require_once 'db.php';

// Verificação flexível da sessão para evitar loop de redirecionamento
$userID = $_SESSION['user_id'] ?? $_SESSION['idu'] ?? $_SESSION['IDU'] ?? null;
$userTipo = $_SESSION['user_tipo'] ?? $_SESSION['tipo'] ?? $_SESSION['TIPO'] ?? '';

if (!$userID) {
    header("Location: login.php");
    exit;
}

// --------------------------------------------------
// AÇÃO: ATUALIZAR PERFIL (NOME E FOTO)
// --------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $novoNome = trim($_POST['novo_nome'] ?? '');

    if (!empty($novoNome)) {
        // Colunas em minúsculas para compatibilidade com o PostgreSQL / Supabase
        $stmtName = $pdo->prepare("UPDATE pap_utilizador SET nome = ? WHERE idu = ?");
        $stmtName->execute([$novoNome, $userID]);
        
        $_SESSION['user_nome'] = $novoNome;
        $_SESSION['nome']      = $novoNome;

        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['foto_perfil']['tmp_name'];
            $fileName      = $_FILES['foto_perfil']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = 'user_' . $userID . '_' . time() . '.' . $fileExtension;
                
                // Na Vercel o sistema de ficheiros é apenas de leitura; usa-se a pasta /tmp/
                $uploadFileDir = '/tmp/uploads/';

                if (!is_dir($uploadFileDir)) {
                    @mkdir($uploadFileDir, 0755, true);
                }
                $dest_path = $uploadFileDir . $newFileName;

                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    $stmtFoto = $pdo->prepare("UPDATE pap_utilizador SET FOTO = ? WHERE IDU = ?");
                    $stmtFoto->execute([$newFileName, $userID]);
                }
            }
        }

        header("Location: dashboard.php");
        exit;
    }
}

$stmtUser = $pdo->prepare("SELECT * FROM pap_utilizador WHERE IDU = ?");
$stmtUser->execute([$userID]);
$userData = $stmtUser->fetch();
$userNome = $userData['NOME'];
$userFoto = $userData['FOTO'] ?? '';

// ----------------------------------------------------
// AÇÕES DO ALUNO: CRIAR PROJETO
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_projeto']) && $userTipo === 'Aluno') {
    $tema = trim($_POST['tema']);
    $area = trim($_POST['area']);
    $datafim = !empty($_POST['datafim']) ? $_POST['datafim'] : null;
    $professoresSelecionados = $_POST['id_professores'] ?? [];

    if (!empty($tema) && !empty($area)) {
        // Sintaxe Postgres: RETURNING IDP em vez de lastInsertId()
        $stmt = $pdo->prepare("INSERT INTO pap_projeto (TEMA, AREA, DATAFIM, IDAluno) VALUES (?, ?, ?, ?) RETURNING IDP");
        $stmt->execute([$tema, $area, $datafim, $userID]);
        $idp = $stmt->fetchColumn();

        if (!empty($professoresSelecionados) && is_array($professoresSelecionados)) {
            $stmtProf = $pdo->prepare("INSERT INTO pap_projeto_orientador (IDP, IDProfessor) VALUES (?, ?)");
            foreach ($professoresSelecionados as $idProf) {
                $stmtProf->execute([$idp, (int)$idProf]);
            }
        }

        header("Location: dashboard.php");
        exit;
    }
}

// ----------------------------------------------------
// AÇÕES DO PROFESSOR: GESTÃO DAS SUAS PASTAS E ALUNOS
// ----------------------------------------------------
if ($userTipo === 'Professor' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['criar_seccao'])) {
        $nomeSeccao = trim($_POST['nome_seccao']);
        $disciplina = trim($_POST['disciplina']);
        $turma = trim($_POST['turma']);

        if (!empty($nomeSeccao) && !empty($disciplina) && !empty($turma)) {
            $stmt = $pdo->prepare("INSERT INTO pap_seccao (NOME, DISCIPLINA, TURMA, IDProfessor) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nomeSeccao, $disciplina, $turma, $userID]);
            header("Location: dashboard.php");
            exit;
        }
    }

    if (isset($_POST['atribuir_seccao'])) {
        $idp = (int)$_POST['idp'];
        $ids = (int)$_POST['ids'];

        if ($idp > 0) {
            $stmtDel = $pdo->prepare("DELETE FROM pap_seccao_projeto WHERE IDP = ? AND IDS IN (SELECT IDS FROM pap_seccao WHERE IDProfessor = ?)");
            $stmtDel->execute([$idp, $userID]);

            if ($ids > 0) {
                $stmtAdd = $pdo->prepare("INSERT INTO pap_seccao_projeto (IDS, IDP) VALUES (?, ?)");
                $stmtAdd->execute([$ids, $idp]);
            }
            header("Location: dashboard.php");
            exit;
        }
    }

    if (isset($_POST['eliminar_seccao'])) {
        $ids = (int)$_POST['ids'];
        $stmtDelSec = $pdo->prepare("DELETE FROM pap_seccao WHERE IDS = ? AND IDProfessor = ?");
        $stmtDelSec->execute([$ids, $userID]);
        header("Location: dashboard.php");
        exit;
    }
}

// ----------------------------------------------------
// CONSULTAS COMPATÍVEIS COM POSTGRESQL (STRING_AGG)
// ----------------------------------------------------
$professores = $pdo->query("SELECT IDU, NOME FROM pap_utilizador WHERE TIPO = 'Professor' ORDER BY NOME ASC")->fetchAll();

if ($userTipo === 'Aluno') {
    $sql = "SELECT P.*, STRING_AGG(U.NOME, ', ') as \"NomesProfs\" 
            FROM pap_projeto P 
            LEFT JOIN pap_projeto_orientador PO ON P.IDP = PO.IDP 
            LEFT JOIN pap_utilizador U ON PO.IDProfessor = U.IDU 
            WHERE P.IDAluno = ? 
            GROUP BY P.IDP 
            ORDER BY P.TEMA ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userID]);
    $projetos = $stmt->fetchAll();
} else {
    $stmtSec = $pdo->prepare("SELECT * FROM pap_seccao WHERE IDProfessor = ? ORDER BY NOME ASC");
    $stmtSec->execute([$userID]);
    $seccoes = $stmtSec->fetchAll();

    $sqlProjetos = "SELECT P.*, U1.NOME as \"NomeAluno\", STRING_AGG(DISTINCT U2.NOME, ', ') as \"NomesProfs\", SP.IDS as \"IDSeccao\"
                    FROM pap_projeto P 
                    JOIN pap_utilizador U1 ON P.IDAluno = U1.IDU 
                    LEFT JOIN pap_projeto_orientador PO ON P.IDP = PO.IDP 
                    LEFT JOIN pap_utilizador U2 ON PO.IDProfessor = U2.IDU 
                    LEFT JOIN pap_seccao_projeto SP ON P.IDP = SP.IDP AND SP.IDS IN (SELECT IDS FROM pap_seccao WHERE IDProfessor = ?)
                    GROUP BY P.IDP, U1.NOME, SP.IDS 
                    ORDER BY U1.NOME ASC, P.TEMA ASC";
    $stmtProj = $pdo->prepare($sqlProjetos);
    $stmtProj->execute([$userID]);
    $projetos = $stmtProj->fetchAll();
}

$projsSemPasta = array_filter($projetos, function($p) {
    return empty($p['IDSeccao']);
});
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Principal | Project Flow</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --primary: #2563eb; --primary-hover: #1d4ed8; --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --border: #e2e8f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text); padding-bottom: 60px; }
        
        .navbar { background: white; border-bottom: 1px solid var(--border); padding: 0 32px; height: 64px; display: flex; align-items: center; justify-content: space-between; }
        .brand { font-size: 1.2rem; font-weight: 800; display: flex; align-items: center; gap: 8px; color: var(--text); }
        .brand i { color: var(--primary); }
        .user-menu { display: flex; align-items: center; gap: 16px; }
        .badge { background: #eff6ff; color: var(--primary); padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .logout { color: #ef4444; text-decoration: none; font-size: 0.9rem; font-weight: 600; display: flex; align-items: center; gap: 4px; }

        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px; }
        .stat-card { background: white; border: 1px solid var(--border); padding: 24px; border-radius: 12px; display: flex; align-items: center; justify-content: space-between; }
        .stat-card h3 { font-size: 0.875rem; color: #64748b; font-weight: 500; }
        .stat-card .val { font-size: 1.8rem; font-weight: 800; color: var(--text); margin-top: 4px; }
        .stat-icon { width: 44px; height: 44px; background: #eff6ff; color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }

        .card { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 28px; margin-bottom: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card h2 { font-size: 1.15rem; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        .folder-card { background: white; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 28px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.03); }
        .folder-header { background: #f8fafc; border-bottom: 1px solid var(--border); padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .folder-title { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.05rem; color: var(--text); }
        .folder-title i { font-size: 1.4rem; color: var(--primary); }
        .folder-pills { display: flex; gap: 8px; margin-left: 10px; }
        .pill { background: #e2e8f0; color: #475569; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; }

        .folder-actions-bar { background: #ffffff; border-bottom: 1px solid var(--border); padding: 12px 24px; display: flex; align-items: center; gap: 12px; }

        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: span 3; }
        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        input, select { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 0.95rem; outline: none; background: white; }

        .professors-list { max-height: 130px; overflow-y: auto; border: 1px solid var(--border); padding: 12px; border-radius: 8px; background: #f8fafc; }
        .prof-item { display: flex; align-items: center; gap: 10px; padding: 4px 0; font-size: 0.9rem; cursor: pointer; }
        .prof-item input { width: auto; margin: 0; }

        .btn { background: var(--primary); color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; font-size: 0.9rem; }
        .btn:hover { background: var(--primary-hover); }
        .btn-sm { padding: 6px 14px; font-size: 0.82rem; }
        .btn-danger { background: #ef4444; color: white; padding: 6px 12px; font-size: 0.8rem; }
        .btn-danger:hover { background: #dc2626; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px 20px; border-bottom: 1px solid var(--border); text-align: left; font-size: 0.92rem; }
        th { color: #64748b; font-weight: 600; background: #ffffff; }
        .btn-open { background: #f1f5f9; color: var(--text); padding: 6px 12px; border-radius: 6px; font-weight: 600; text-decoration: none; font-size: 0.85rem; transition: 0.2s; }
        .btn-open:hover { background: var(--primary); color: white; }
        
        select.move-select { padding: 6px 10px; font-size: 0.82rem; border-radius: 6px; border: 1px solid var(--border); background: #f8fafc; width: auto; cursor: pointer; }

        body { background: #f4f7fc; }
        .navbar { position: sticky; top: 0; z-index: 100; height: 68px; padding: 0 30px; box-shadow: 0 1px 0 rgba(15,23,42,.04); }
        .brand { font-size: 1.15rem; letter-spacing: -.35px; }
        .brand i { font-size: 1.5rem; }
        
        .sidebar {
            position: fixed; z-index: 90; top: 68px; left: 0; bottom: 0;
            width: 250px; background: #fff; border-right: 1px solid #e5eaf3;
            padding: 24px 16px 20px; display: flex; flex-direction: column;
        }
        .sidebar-caption { padding: 0 12px; margin: 16px 0 8px; color: #94a3b8; font-size: .68rem; font-weight: 800; letter-spacing: 1.15px; }
        .sidebar-caption:first-child { margin-top: 4px; }
        
        .side-link { min-height: 42px; padding: 0 12px; display: flex; align-items: center; gap: 12px; margin: 6px 0; color: #526079; text-decoration: none; border-radius: 10px; font-size: .88rem; font-weight: 600; transition: background .18s, color .18s; cursor: pointer; }
        .side-link i { font-size: 1.25rem; color: #8290a8; }
        .side-link:hover { background: #f2f6ff; color: var(--primary); }
        .side-link:hover i { color: var(--primary); }
        .side-link.active { color: #1d4ed8; background: #eaf1ff; }
        .side-link.active i { color: #2563eb; }
        
        .sidebar-divider { height: 1px; background: #edf0f6; margin: 16px 8px; }
        
        .sidebar-user { display: flex; align-items: center; gap: 10px; padding: 12px; margin-top: 6px; border: 1px solid #edf0f6; border-radius: 12px; }
        .avatar { flex: 0 0 36px; height: 36px; display: grid; place-items: center; border-radius: 11px; background: linear-gradient(145deg,#3978f6,#2353d8); color: #fff; font-weight: 800; font-size: .95rem; overflow: hidden; }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .sidebar-user-info { min-width: 0; display: flex; flex-direction: column; gap: 4px; }
        .sidebar-user-info strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .82rem; }
        .sidebar-user-info span { color: #7b879b; font-size: .74rem; }
        
        .side-logout { margin-top: 8px; color: #d9535f; }
        .side-logout i { color: #d9535f; }
        .side-logout:hover { color: #c62838; background: #fff1f2; }
        .sidebar-footer { margin-top: auto; padding-top: 14px; color: #64748b; font-size: .74rem; font-weight: 700; border-top: 1px solid #edf0f6; }
        .status-dot { display: inline-block; width: 7px; height: 7px; background: #22c55e; border-radius: 50%; margin-right: 6px; }
        
        .container { max-width: none; margin: 0 0 0 250px; padding: 32px 34px 56px; }
        
        .tab-section { display: none; }
        .tab-section.active-tab { display: block; }

        .stats-grid { gap: 16px; margin-bottom: 24px; }
        .stat-card { border-radius: 15px; padding: 22px 24px; box-shadow: 0 3px 12px rgba(22,45,90,.025); transition: transform .18s, box-shadow .18s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(22,45,90,.06); }
        .stat-icon { border-radius: 12px; background: #edf4ff; }
        .card, .folder-card { border-radius: 15px; border-color: #e6ebf3; box-shadow: 0 3px 12px rgba(22,45,90,.025); }
        .card { padding: 26px; margin-bottom: 24px; }
        .card h2 { font-size: 1.08rem; letter-spacing: -.2px; }
        input, select { min-height: 42px; border-color: #dfe6f0; transition: border-color .18s, box-shadow .18s; }
        input:focus, select:focus { border-color: #7aa5ff; box-shadow: 0 0 0 3px rgba(37,99,235,.10); }
        .professors-list { background: #f8faff; border-color: #e4eaf4; }
        .btn { border-radius: 9px; box-shadow: 0 2px 4px rgba(37,99,235,.12); }
        .folder-header { background: #f8faff; }
        th { background: #fbfcff; }
        tbody tr:hover { background: #f8faff; }
        .btn-open { background: #edf3ff; color: #2458c7; }
        .btn-open:hover { background: var(--primary); }

        @media (max-width: 900px) {
            .sidebar { width: 205px; padding-left: 12px; padding-right: 12px; }
            .container { margin-left: 205px; padding: 24px 20px 40px; }
            .stats-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr 1fr; }
            .form-group.full { grid-column: span 2; }
        }
        @media (max-width: 640px) {
            .navbar { padding: 0 16px; height: 60px; }
            .brand { font-size: 1rem; }
            .user-menu { gap: 8px; font-size: .8rem; }
            .sidebar { position: static; top: auto; bottom: auto; width: auto; padding: 10px 12px; border-right: 0; border-bottom: 1px solid #e5eaf3; display: block; }
            .sidebar-caption, .sidebar-divider, .sidebar-user, .sidebar-footer, .side-logout { display: none; }
            .side-link { display: inline-flex; min-height: 38px; padding: 0 10px; margin: 2px; font-size: .78rem; }
            .side-link i { font-size: 1.05rem; }
            .container { margin: 0; padding: 18px 12px 32px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: span 1; }
            .card { padding: 18px 14px; overflow-x: auto; }
            .folder-actions-bar { padding: 12px; }
            .folder-actions-bar form { flex-wrap: wrap; }
            table { min-width: 680px; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="brand"><i class="ph-fill ph-kanban"></i> Project Flow</div>
        <div class="user-menu">
            <span><strong><?= htmlspecialchars($userNome) ?></strong></span>
            <span class="badge"><?= htmlspecialchars($userTipo) ?></span>
            <a href="logout.php" class="logout"><i class="ph ph-sign-out"></i> Sair</a>
        </div>
    </nav>

    <aside class="sidebar">
        <div class="sidebar-caption">GERAL</div>
        <a class="side-link active" onclick="switchTab('overview', this)"><i class="ph ph-squares-four"></i><span>Visão geral</span></a>
        
        <div class="sidebar-caption">CONTA</div>
        <a class="side-link" onclick="switchTab('editar-perfil', this)"><i class="ph ph-user-gear"></i><span>Editar perfil</span></a>

        <div class="sidebar-caption">GESTOR</div>
        <?php if ($userTipo === 'Aluno'): ?>
            <a class="side-link" onclick="switchTab('novo-projeto', this)"><i class="ph ph-plus-circle"></i><span>Novo projeto</span></a>
            <a class="side-link" onclick="switchTab('projetos-section', this)"><i class="ph ph-folder-open"></i><span>Os meus projetos</span></a>
        <?php else: ?>
            <a class="side-link" onclick="switchTab('novo-projeto', this)"><i class="ph ph-folder-plus"></i><span>Criar pasta</span></a>
            <a class="side-link" onclick="switchTab('projetos-section', this)"><i class="ph ph-folders"></i><span>As minhas pastas</span></a>
        <?php endif; ?>
        
        <div class="sidebar-divider"></div>
        
        <div class="sidebar-user">
            <div class="avatar">
                <?php if (!empty($userFoto) && file_exists(__DIR__ . '/uploads/' . $userFoto)): ?>
                    <img src="uploads/<?= htmlspecialchars($userFoto) ?>" alt="Foto de Perfil">
                <?php else: ?>
                    <?= htmlspecialchars(mb_strtoupper(mb_substr($userNome, 0, 1, 'UTF-8'), 'UTF-8')) ?>
                <?php endif; ?>
            </div>
            <div class="sidebar-user-info">
                <strong><?= htmlspecialchars($userNome) ?></strong>
                <span><?= htmlspecialchars($userTipo) ?></span>
            </div>
        </div>
        
        <a class="side-link side-logout" href="logout.php"><i class="ph ph-sign-out"></i><span>Terminar sessão</span></a>
        <div class="sidebar-footer"><span class="status-dot"></span> Project Flow</div>
    </aside>

    <div class="container">

        <!-- 1. VISÃO GERAL -->
        <div id="overview" class="tab-section active-tab">
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <h3>Total de Projetos</h3>
                        <div class="val"><?= count($projetos) ?></div>
                    </div>
                    <div class="stat-icon"><i class="ph ph-folder"></i></div>
                </div>
                <div class="stat-card">
                    <div>
                        <h3>Perfil Ativo</h3>
                        <div class="val"><?= htmlspecialchars($userTipo) ?></div>
                    </div>
                    <div class="stat-icon"><i class="ph ph-user-check"></i></div>
                </div>
                <div class="stat-card">
                    <div>
                        <h3><?= $userTipo === 'Professor' ? 'Minhas Pastas' : 'Estado' ?></h3>
                        <div class="val"><?= $userTipo === 'Professor' ? count($seccoes) : 'Em Curso' ?></div>
                    </div>
                    <div class="stat-icon"><i class="ph ph-folder-star"></i></div>
                </div>
            </div>
            <div class="card">
                <h2><i class="ph ph-info" style="color: var(--primary);"></i> Bem-vindo ao Painel</h2>
                <p>Selecione uma opção no menu lateral para gerir o seu perfil, criar pastas ou submeter novos projetos.</p>
            </div>
        </div>

        <!-- 2. EDITAR PERFIL -->
        <div id="editar-perfil" class="tab-section">
            <div class="card">
                <h2><i class="ph ph-user-circle-gear" style="color: var(--primary);"></i> Editar O Meu Perfil</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="atualizar_perfil" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome do Utilizador</label>
                            <input type="text" name="novo_nome" value="<?= htmlspecialchars($userNome) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>E-mail (Não alterável)</label>
                            <input type="email" value="<?= htmlspecialchars($userData['EMAIL']) ?>" disabled style="background: #f1f5f9; cursor: not-allowed; color: #64748b;">
                        </div>
                        <div class="form-group">
                            <label>Foto de Perfil (JPG, PNG ou WEBP)</label>
                            <input type="file" name="foto_perfil" accept=".jpg, .jpeg, .png, .webp">
                        </div>
                    </div>
                    <button type="submit" class="btn"><i class="ph ph-floppy-disk"></i> Guardar Alterações</button>
                </form>
            </div>
        </div>

        <!-- 3. CRIAR PASTA / NOVO PROJETO -->
        <div id="novo-projeto" class="tab-section">
            <?php if ($userTipo === 'Professor'): ?>
            <div class="card">
                <h2><i class="ph ph-folder-plus" style="color: var(--primary);"></i> Criar Minha Pasta de Organização</h2>
                <form method="POST">
                    <input type="hidden" name="criar_seccao" value="1">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Nome da Pasta/Secção</label>
                            <input type="text" name="nome_seccao" placeholder="Ex: Avaliação Intermédia" required>
                        </div>
                        <div class="form-group">
                            <label>Disciplina</label>
                            <input type="text" name="disciplina" placeholder="Ex: PSI" required>
                        </div>
                        <div class="form-group">
                            <label>Turma</label>
                            <input type="text" name="turma" placeholder="Ex: 12º GPSI" required>
                        </div>
                    </div>
                    <button type="submit" class="btn"><i class="ph ph-folder-notch-plus"></i> Criar Pasta</button>
                </form>
            </div>
            <?php else: ?>
            <div class="card">
                <h2><i class="ph ph-plus-circle" style="color: var(--primary);"></i> Registar Novo Projeto PAP</h2>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label>Tema / Título do Projeto</label>
                            <input type="text" name="tema" placeholder="Ex: Aplicação Web de Gestão de Inventário" required>
                        </div>
                        <div class="form-group">
                            <label>Área Tecnológica</label>
                            <input type="text" name="area" placeholder="Ex: Programação Web" required>
                        </div>
                        <div class="form-group">
                            <label>Data Prevista de Conclusão</label>
                            <input type="date" name="datafim">
                        </div>
                        <div class="form-group full">
                            <label>Selecione o(s) Tutor(es) / Orientador(es):</label>
                            <div class="professors-list">
                                <?php foreach ($professores as $p): ?>
                                    <label class="prof-item">
                                        <input type="checkbox" name="id_professores[]" value="<?= $p['IDU'] ?>">
                                        <?= htmlspecialchars($p['NOME']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="criar_projeto" class="btn" style="margin-top: 12px;"><i class="ph ph-paper-plane-right"></i> Submeter Projeto</button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- 4. PASTAS OU PROJETOS DO UTILIZADOR -->
        <div id="projetos-section" class="tab-section">
            <?php if ($userTipo === 'Professor'): ?>

                <h2 style="font-size: 1.2rem; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-folders" style="color: var(--primary);"></i> As Minhas Pastas Privadas (Ordem Alfabética)
                </h2>

                <?php foreach ($seccoes as $sec): ?>
                    <?php 
                        $projsPasta = array_values(array_filter($projetos, function($p) use ($sec) {
                            return $p['IDSeccao'] == $sec['IDS'];
                        }));
                    ?>
                    <div class="folder-card">
                        <div class="folder-header">
                            <div class="folder-title">
                                <i class="ph-fill ph-folder-open"></i>
                                <?= htmlspecialchars($sec['NOME']) ?>
                                <div class="folder-pills">
                                    <span class="pill"><i class="ph ph-book-bookmark"></i> <?= htmlspecialchars($sec['DISCIPLINA']) ?></span>
                                    <span class="pill"><i class="ph ph-users-three"></i> <?= htmlspecialchars($sec['TURMA']) ?></span>
                                </div>
                            </div>
                            <form method="POST" onsubmit="return confirm('Deseja apagar esta pasta? Os alunos não serão eliminados.');" style="margin:0;">
                                <input type="hidden" name="eliminar_seccao" value="1">
                                <input type="hidden" name="ids" value="<?= $sec['IDS'] ?>">
                                <button type="submit" class="btn btn-danger"><i class="ph ph-trash"></i> Eliminar Pasta</button>
                            </form>
                        </div>

                        <div class="folder-actions-bar">
                            <form method="POST" style="display: flex; gap: 10px; align-items: center; width: 100%; margin: 0;">
                                <input type="hidden" name="atribuir_seccao" value="1">
                                <input type="hidden" name="ids" value="<?= $sec['IDS'] ?>">
                                <span style="font-size: 0.85rem; font-weight: 600; color: #475569; white-space: nowrap;"><i class="ph ph-user-plus" style="color: var(--primary);"></i> Adicionar Aluno a esta pasta:</span>
                                <select name="idp" class="move-select" required style="flex: 1;">
                                    <option value="">-- Escolher Aluno / Projeto Não Atribuído --</option>
                                    <?php foreach ($projsSemPasta as $pSem): ?>
                                        <option value="<?= $pSem['IDP'] ?>"><?= htmlspecialchars($pSem['NomeAluno']) ?> — "<?= htmlspecialchars($pSem['TEMA']) ?>"</option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm"><i class="ph ph-plus"></i> Adicionar</button>
                            </form>
                        </div>
                        
                        <table>
                            <thead>
                                <tr>
                                    <th>Aluno (A-Z)</th>
                                    <th>Tema do Projeto</th>
                                    <th>Área</th>
                                    <th>Orientadores</th>
                                    <th>Ação</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($projsPasta) > 0): ?>
                                    <?php foreach ($projsPasta as $p): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($p['NomeAluno']) ?></strong></td>
                                            <td><?= htmlspecialchars($p['TEMA']) ?></td>
                                            <td><?= htmlspecialchars($p['AREA']) ?></td>
                                            <td><?= htmlspecialchars($p['NomesProfs'] ?? 'Sem orientador') ?></td>
                                            <td>
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="atribuir_seccao" value="1">
                                                    <input type="hidden" name="idp" value="<?= $p['IDP'] ?>">
                                                    <select name="ids" class="move-select" onchange="this.form.submit()">
                                                        <option value="<?= $sec['IDS'] ?>">📁 <?= htmlspecialchars($sec['NOME']) ?></option>
                                                        <option value="0">&bull; Remover da Pasta</option>
                                                        <?php foreach ($seccoes as $s): ?>
                                                            <?php if ($s['IDS'] != $sec['IDS']): ?>
                                                                <option value="<?= $s['IDS'] ?>">📁 Mover p/ <?= htmlspecialchars($s['NOME']) ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </form>
                                            </td>
                                            <td><a href="index.php?id=<?= $p['IDP'] ?>" class="btn-open">Aceder &rarr;</a></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 18px;">Esta pasta está vazia. Adicione alunos no seletor acima.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; ?>

                <div class="folder-card" style="border-style: dashed;">
                    <div class="folder-header" style="background: #ffffff;">
                        <div class="folder-title" style="color: #64748b;">
                            <i class="ph ph-folder-dashed" style="color: #94a3b8;"></i>
                            Alunos / Projetos Fora de Pastas
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Aluno (A-Z)</th>
                                <th>Tema do Projeto</th>
                                <th>Área</th>
                                <th>Orientadores</th>
                                <th>Mover para Pasta</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projsSemPasta) > 0): ?>
                                <?php foreach ($projsSemPasta as $p): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($p['NomeAluno']) ?></strong></td>
                                        <td><?= htmlspecialchars($p['TEMA']) ?></td>
                                        <td><?= htmlspecialchars($p['AREA']) ?></td>
                                        <td><?= htmlspecialchars($p['NomesProfs'] ?? 'Sem orientador') ?></td>
                                        <td>
                                            <form method="POST" style="margin:0;">
                                                <input type="hidden" name="atribuir_seccao" value="1">
                                                <input type="hidden" name="idp" value="<?= $p['IDP'] ?>">
                                                <select name="ids" class="move-select" onchange="this.form.submit()">
                                                    <option value="0">-- Mover para... --</option>
                                                    <?php foreach ($seccoes as $s): ?>
                                                        <option value="<?= $s['IDS'] ?>">📁 <?= htmlspecialchars($s['NOME']) ?> (<?= htmlspecialchars($s['TURMA']) ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </td>
                                        <td><a href="index.php?id=<?= $p['IDP'] ?>" class="btn-open">Aceder &rarr;</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 18px;">Todos os alunos já estão guardados em pastas!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>
                <div class="card">
                    <h2><i class="ph ph-kanban" style="color: var(--primary);"></i> Os Meus Projetos PAP</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tema do Projeto</th>
                                <th>Área</th>
                                <th>Orientadores / Tutores</th>
                                <th>Data Fim</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($projetos) > 0): ?>
                                <?php foreach ($projetos as $p): ?>
                                    <tr>
                                        <td>#<?= $p['IDP'] ?></td>
                                        <td><strong><?= htmlspecialchars($p['TEMA']) ?></strong></td>
                                        <td><?= htmlspecialchars($p['AREA']) ?></td>
                                        <td><?= htmlspecialchars($p['NomesProfs'] ?? 'Sem orientador') ?></td>
                                        <td><?= $p['DATAFIM'] ?? 'Não definida' ?></td>
                                        <td><a href="index.php?id=<?= $p['IDP'] ?>" class="btn-open">Aceder &rarr;</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 24px;">Nenhum projeto registado até ao momento.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        function switchTab(tabId, element) {
            const sections = document.querySelectorAll('.tab-section');
            sections.forEach(sec => sec.classList.remove('active-tab'));

            document.getElementById(tabId).classList.add('active-tab');

            const links = document.querySelectorAll('.side-link');
            links.forEach(link => link.classList.remove('active'));

            element.classList.add('active');
        }
    </script>
</body>
</html>
