<?php
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$idp = (int)$_GET['id'];
$userID = $_SESSION['user_id'];
$userTipo = $_SESSION['user_tipo'];

// Obter detalhes do projeto
$stmt = $pdo->prepare("SELECT P.*, U1.NOME as NomeAluno, U2.NOME as NomeProf 
                        FROM pap_projeto P 
                        JOIN pap_utilizador U1 ON P.IDAluno = U1.IDU 
                        LEFT JOIN pap_utilizador U2 ON P.IDProfessor = U2.IDU 
                        WHERE P.IDP = ?");
$stmt->execute([$idp]);
$projeto = $stmt->fetch();

if (!$projeto) {
    die("Projeto não encontrado.");
}

// Ações de criação/edição permitidas apenas a ALUNOS
if ($userTipo === 'Aluno') {
    // 1. Adicionar Tarefa
    if (isset($_POST['add_tarefa'])) {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $datafinal = $_POST['datafinal'];
        if (!empty($titulo)) {
            $stmt = $pdo->prepare("INSERT INTO pap_tarefa (TITULO, DESCRICAO, DATAFINAL, IDP) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $datafinal, $idp]);
            header("Location: index.php?id=$idp");
            exit;
        }
    }

    // Alterar Estado da Tarefa
    if (isset($_POST['update_tarefa_estado'])) {
        $idt = (int)$_POST['idt'];
        $estado = $_POST['estado'];
        $stmt = $pdo->prepare("UPDATE pap_tarefa SET ESTADO = ? WHERE IDT = ? AND IDP = ?");
        $stmt->execute([$estado, $idt, $idp]);
        header("Location: index.php?id=$idp");
        exit;
    }

    // 2. Adicionar Registo Diário
    if (isset($_POST['add_registo'])) {
        $descricao = trim($_POST['descricao']);
        if (!empty($descricao)) {
            $stmt = $pdo->prepare("INSERT INTO pap_registo (DESCRICAO, IDP) VALUES (?, ?)");
            $stmt->execute([$descricao, $idp]);
            header("Location: index.php?id=$idp");
            exit;
        }
    }

    // 3. Adicionar Recurso
    if (isset($_POST['add_recurso'])) {
        $titulo = trim($_POST['titulo']);
        $descricao = trim($_POST['descricao']);
        $link = trim($_POST['link']);
        if (!empty($titulo) && !empty($link)) {
            $stmt = $pdo->prepare("INSERT INTO pap_recurso (TITULO, DESCRICAO, LINK, IDP) VALUES (?, ?, ?, ?)");
            $stmt->execute([$titulo, $descricao, $link, $idp]);
            header("Location: index.php?id=$idp");
            exit;
        }
    }
}

// 4. Adicionar Feedback (Apenas Professor)
if (isset($_POST['add_feedback']) && $userTipo === 'Professor') {
    $comentario = trim($_POST['comentario']);
    if (!empty($comentario)) {
        $stmt = $pdo->prepare("INSERT INTO pap_feedback (COMENTARIO, IDP, IDProfessor) VALUES (?, ?, ?)");
        $stmt->execute([$comentario, $idp, $userID]);
        header("Location: index.php?id=$idp");
        exit;
    }
}

// Consultar dados
$tarefas = $pdo->query("SELECT * FROM pap_tarefa WHERE IDP = $idp ORDER BY IDT DESC")->fetchAll();
$registos = $pdo->query("SELECT * FROM pap_registo WHERE IDP = $idp ORDER BY DATA DESC")->fetchAll();
$recursos = $pdo->query("SELECT * FROM pap_recurso WHERE IDP = $idp ORDER BY DATA DESC")->fetchAll();
$feedbacks = $pdo->query("SELECT F.*, U.NOME as NomeProf FROM pap_feedback F JOIN pap_utilizador U ON F.IDProfessor = U.IDU WHERE F.IDP = $idp ORDER BY F.DATA DESC")->fetchAll();

// Calcular % de conclusão
$totalTarefas = count($tarefas);
$concluidas = 0;
foreach ($tarefas as $t) {
    if ($t['ESTADO'] === 'Concluído') $concluidas++;
}
$percentagem = $totalTarefas > 0 ? round(($concluidas / $totalTarefas) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espaço de Trabalho: <?= htmlspecialchars($projeto['TEMA']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        :root { --primary: #2563eb; --primary-hover: #1d4ed8; --bg: #f8fafc; --card: #ffffff; --text: #0f172a; --border: #e2e8f0; }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text); padding-bottom: 60px; }
        
        .top-bar { background: white; border-bottom: 1px solid var(--border); padding: 16px 32px; display: flex; justify-content: space-between; align-items: center; }
        .back-link { text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 6px; }
        .back-link:hover { color: var(--primary); }

        .container { max-width: 1200px; margin: 32px auto; padding: 0 20px; }
        
        .project-header { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 32px; margin-bottom: 32px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .project-header h1 { font-size: 1.8rem; font-weight: 800; margin-bottom: 8px; }
        .meta-tags { display: flex; gap: 16px; color: #64748b; font-size: 0.9rem; margin-bottom: 20px; flex-wrap: wrap; }
        .meta-tags span { display: flex; align-items: center; gap: 6px; }

        .progress-bar-bg { background: #e2e8f0; height: 10px; border-radius: 20px; overflow: hidden; width: 100%; }
        .progress-bar-fill { background: var(--primary); height: 100%; border-radius: 20px; transition: width 0.4s ease; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .box { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .box h2 { font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); padding-bottom: 12px; }

        input, textarea, select { width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 12px; font-size: 0.9rem; outline: none; background: white; }
        button { background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
        button:hover { background: var(--primary-hover); }

        .list-item { background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-top: 12px; }
        .badge-status { padding: 3px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; }
        .bg-pendente { background: #fef3c7; color: #b45309; }
        .bg-progresso { background: #e0f2fe; color: #0369a1; }
        .bg-concluido { background: #dcfce7; color: #15803d; }

        .timeline-date { font-size: 0.75rem; color: #94a3b8; font-weight: 600; }
        .empty-msg { font-size: 0.875rem; color: #94a3b8; text-align: center; padding: 12px 0; }
    </style>
</head>
<body>

    <div class="top-bar">
        <a href="dashboard.php" class="back-link"><i class="ph ph-arrow-left"></i> Voltar ao Painel Geral</a>
        <span style="font-weight: 600; font-size: 0.9rem; color: #64748b;">PAP Manager &bull; Espaço de Trabalho</span>
    </div>

    <div class="container">

        <!-- Banner do Projeto -->
        <div class="project-header">
            <h1><?= htmlspecialchars($projeto['TEMA']) ?></h1>
            <div class="meta-tags">
                <span><i class="ph ph-tag"></i> <strong>Área:</strong> <?= htmlspecialchars($projeto['AREA']) ?></span>
                <span><i class="ph ph-user"></i> <strong>Aluno:</strong> <?= htmlspecialchars($projeto['NomeAluno']) ?></span>
                <span><i class="ph ph-user-gear"></i> <strong>Orientador:</strong> <?= htmlspecialchars($projeto['NomeProf'] ?? 'Não atribuído') ?></span>
            </div>
            <div>
                <div style="display: flex; justify-content: space-between; font-size: 0.85rem; font-weight: 700; margin-bottom: 6px;">
                    <span>Progresso Global do Projeto</span>
                    <span><?= $percentagem ?>% Concluído</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: <?= $percentagem ?>%;"></div>
                </div>
            </div>
        </div>

        <div class="grid">

            <!-- 1. TAREFAS -->
            <div class="box">
                <h2><i class="ph ph-check-square" style="color: var(--primary);"></i> 1. Planeamento & Tarefas</h2>
                
                <?php if ($userTipo === 'Aluno'): ?>
                    <form method="POST">
                        <input type="text" name="titulo" placeholder="Título da tarefa..." required>
                        <textarea name="descricao" placeholder="Descrição dos objetivos da tarefa..." rows="2"></textarea>
                        <input type="date" name="datafinal">
                        <button type="submit" name="add_tarefa"><i class="ph ph-plus"></i> Adicionar Tarefa</button>
                    </form>
                <?php endif; ?>

                <div style="margin-top: 16px;">
                    <?php if (count($tarefas) > 0): ?>
                        <?php foreach ($tarefas as $t): ?>
                            <div class="list-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                    <strong><?= htmlspecialchars($t['TITULO']) ?></strong>
                                    <span class="badge-status <?= $t['ESTADO'] == 'Concluído' ? 'bg-concluido' : ($t['ESTADO'] == 'Em Progresso' ? 'bg-progresso' : 'bg-pendente') ?>">
                                        <?= $t['ESTADO'] ?>
                                    </span>
                                </div>
                                <p style="font-size: 0.875rem; color: #475569; margin-bottom: 8px;"><?= htmlspecialchars($t['DESCRICAO']) ?></p>
                                
                                <?php if ($userTipo === 'Aluno'): ?>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="idt" value="<?= $t['IDT'] ?>">
                                        <input type="hidden" name="update_tarefa_estado" value="1">
                                        <select name="estado" onchange="this.form.submit()" style="margin: 0; padding: 4px 8px; font-size: 0.8rem; width: auto;">
                                            <option value="Pendente" <?= $t['ESTADO'] == 'Pendente' ? 'selected' : '' ?>>Marcar Pendente</option>
                                            <option value="Em Progresso" <?= $t['ESTADO'] == 'Em Progresso' ? 'selected' : '' ?>>Marcar Em Progresso</option>
                                            <option value="Concluído" <?= $t['ESTADO'] == 'Concluído' ? 'selected' : '' ?>>Marcar Concluído</option>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-msg">Nenhuma tarefa adicionada até ao momento.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 2. DIÁRIO DE BORDO -->
            <div class="box">
                <h2><i class="ph ph-notebook" style="color: var(--primary);"></i> 2. Diário de Bordo (Registos)</h2>
                
                <?php if ($userTipo === 'Aluno'): ?>
                    <form method="POST">
                        <textarea name="descricao" placeholder="Descreva brevemente o trabalho realizado hoje..." rows="3" required></textarea>
                        <button type="submit" name="add_registo"><i class="ph ph-note-pencil"></i> Guardar Registo</button>
                    </form>
                <?php endif; ?>

                <div style="margin-top: 16px;">
                    <?php if (count($registos) > 0): ?>
                        <?php foreach ($registos as $r): ?>
                            <div class="list-item">
                                <div class="timeline-date"><i class="ph ph-clock"></i> <?= date('d/m/Y H:i', strtotime($r['DATA'])) ?></div>
                                <p style="font-size: 0.9rem; margin-top: 4px;"><?= nl2br(htmlspecialchars($r['DESCRICAO'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-msg">Nenhum registo efetuado até ao momento.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. RECURSOS -->
            <div class="box">
                <h2><i class="ph ph-link-simple" style="color: var(--primary);"></i> 3. Recursos & Documentação</h2>
                
                <?php if ($userTipo === 'Aluno'): ?>
                    <form method="POST">
                        <input type="text" name="titulo" placeholder="Nome do recurso (ex: Documentação Laravel)" required>
                        <input type="url" name="link" placeholder="https://..." required>
                        <textarea name="descricao" placeholder="Notas sobre o link..." rows="2"></textarea>
                        <button type="submit" name="add_recurso"><i class="ph ph-bookmark"></i> Adicionar Recurso</button>
                    </form>
                <?php endif; ?>

                <div style="margin-top: 16px;">
                    <?php if (count($recursos) > 0): ?>
                        <?php foreach ($recursos as $rec): ?>
                            <div class="list-item">
                                <strong><?= htmlspecialchars($rec['TITULO']) ?></strong>
                                <p style="font-size: 0.85rem; color: #64748b; margin: 4px 0;"><?= htmlspecialchars($rec['DESCRICAO']) ?></p>
                                <a href="<?= htmlspecialchars($rec['LINK']) ?>" target="_blank" style="color: var(--primary); font-weight: 600; font-size: 0.85rem; text-decoration: none;">Abrir Recurso external &rarr;</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-msg">Nenhum recurso guardado até ao momento.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 4. FEEDBACK DOS PROFESSORES -->
            <div class="box">
                <h2><i class="ph ph-chats-circle" style="color: var(--primary);"></i> 4. Avaliação & Feedback Docente</h2>
                
                <?php if ($userTipo === 'Professor'): ?>
                    <form method="POST">
                        <textarea name="comentario" placeholder="Escreva observações ou correções para o aluno..." rows="3" required></textarea>
                        <button type="submit" name="add_feedback"><i class="ph ph-paper-plane-tilt"></i> Enviar Feedback</button>
                    </form>
                <?php else: ?>
                    <p style="font-size: 0.85rem; color: #64748b;">Notas deixadas pelo seu professor orientador sobre a evolução do projeto.</p>
                <?php endif; ?>

                <div style="margin-top: 16px;">
                    <?php if (count($feedbacks) > 0): ?>
                        <?php foreach ($feedbacks as $f): ?>
                            <div class="list-item" style="border-left: 4px solid var(--primary);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <strong>Prof. <?= htmlspecialchars($f['NomeProf']) ?></strong>
                                    <span class="timeline-date"><?= date('d/m/Y', strtotime($f['DATA'])) ?></span>
                                </div>
                                <p style="font-size: 0.9rem; margin-top: 8px;"><?= nl2br(htmlspecialchars($f['COMENTARIO'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="empty-msg">Ainda não foi registado feedback docente.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</body>
</html>