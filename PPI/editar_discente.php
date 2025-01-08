<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário tem permissão para acessar esta página (exemplo: administrador)
if ($_SESSION['user_type'] !== 'administrador' && $_SESSION['user_type'] !== 'setor') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar a conexão com o banco de dados
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Consultar o nome do setor e a foto de perfil
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Flag para controlar qual parte da página exibir
$displayTurmas = true;
$displayDiscenteInfo = false;
$matricula = null;
$discente_info = null;

// Consultar cursos e turmas
$query_cursos = "SELECT id, nome FROM cursos";
$result_cursos = $conn->query($query_cursos);

// Consultar turmas por curso
$query_turmas = "
    SELECT t.numero, t.ano, t.curso_id, c.nome AS curso_nome
    FROM turmas t
    JOIN cursos c ON t.curso_id = c.id
    ORDER BY c.nome, t.numero;
";
$result_turmas = $conn->query($query_turmas);

// Criar um array associativo para armazenar turmas por curso
$turmas_por_curso = [];
while ($row = $result_turmas->fetch_assoc()) {
    $turmas_por_curso[$row['curso_nome']][] = [
        'numero' => $row['numero'],
        'ano' => $row['ano'],
    ];
}

// Exibir turmas quando um curso é escolhido
if (isset($_GET['turma_numero']) && isset($_GET['turma_ano'])) {
    $turma_numero = intval($_GET['turma_numero']);
    $turma_ano = intval($_GET['turma_ano']);
    $displayTurmas = false; // Exibir discentes, não turmas

    // Consultar os discentes dessa turma
    $query_discentes = "
        SELECT d.numero_matricula, d.nome AS discente_nome
        FROM discentes d
        JOIN discentes_turmas dt ON d.numero_matricula = dt.numero_matricula
        JOIN turmas t ON dt.turma_numero = t.numero
        WHERE t.numero = ? AND t.ano = ?
        ORDER BY d.numero_matricula;
    ";

    $stmt = $conn->prepare($query_discentes);
    $stmt->bind_param("ii", $turma_numero, $turma_ano);
    $stmt->execute();
    $result_discentes = $stmt->get_result();
}

// Exibir informações do discente quando um matrícula é selecionada
if (isset($_GET['matricula'])) {
    $matricula = intval($_GET['matricula']);

    // Consultar as informações do discente
    $query_discente = "
        SELECT 
            d.numero_matricula, d.nome AS discente_nome, 
            d.cidade, d.email, d.genero, d.data_nascimento, d.observacoes,
            d.uf, d.reprovacoes, d.acompanhamento, 
            d.apoio_psicologico, d.auxilio_permanencia, d.cotista, 
            d.estagio, d.acompanhamento_saude, d.projeto_pesquisa, 
            d.projeto_extensao, d.projeto_ensino, d.foto
        FROM discentes d
        WHERE d.numero_matricula = ?;
    ";

    $stmt = $conn->prepare($query_discente);
    $stmt->bind_param("i", $matricula);
    $stmt->execute();
    $result_discente = $stmt->get_result();
    $discente_info = $result_discente->fetch_assoc();

    // Consultar as notas do discente
    $query_notas = "
    SELECT 
    di.nome AS disciplina_nome, 
    MAX(n.parcial_1) AS parcial_1, 
    MAX(n.nota_semestre_1) AS nota_semestre_1,
    MAX(n.parcial_2) AS parcial_2, 
    MAX(n.nota_semestre_2) AS nota_semestre_2,
    MAX(n.ais) AS ais, 
    MAX(n.mostra_ciencias) AS mostra_ciencias, 
    MAX(n.ppi) AS ppi, 
    MAX(n.nota_exame) AS nota_exame,
    MAX(n.nota_final) AS nota_final, 
    MAX(n.faltas) AS faltas, 
    MAX(n.observacoes) AS observacoes
FROM notas n
JOIN disciplinas di ON n.disciplina_id = di.id
WHERE n.discente_id = ? 
GROUP BY di.nome
ORDER BY di.nome;

";

    $stmt = $conn->prepare($query_notas);
    $stmt->bind_param("i", $matricula);
    $stmt->execute();
    $result_notas = $stmt->get_result();

    $displayTurmas = false; // Exibir informações do discente, não turmas
    $displayDiscenteInfo = true;
}

// Processar o formulário de edição do discente
// Verificar se o formulário foi enviado e os parâmetros estão corretos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['matricula'])) {
    // Recuperar os dados do formulário
    $matricula = intval($_POST['matricula']);
    $nome = $_POST['nome'];
    $cidade = $_POST['cidade'];
    $uf = $_POST['uf'];
    $email = $_POST['email'];
    $genero = $_POST['genero'];
    $data_nascimento = $_POST['data_nascimento'];
    $observacoes = $_POST['observacoes'];
    $reprovacoes = $_POST['reprovacoes'];
    // Valores de "Sim" ou "Não"
    $acompanhamento = $_POST['acompanhamento'] ?? 'Não';
    $apoio_psicologico = $_POST['apoio_psicologico'] ?? 'Não';
    $auxilio_permanencia = $_POST['auxilio_permanencia'] ?? 'Não';
    $cotista = $_POST['cotista'] ?? 'Não';
    $estagio = $_POST['estagio'] ?? 'Não';
    $acompanhamento_saude = $_POST['acompanhamento_saude'] ?? 'Não';
    $projeto_pesquisa = $_POST['projeto_pesquisa'] ?? 'Não';
    $projeto_extensao = $_POST['projeto_extensao'] ?? 'Não';
    $projeto_ensino = $_POST['projeto_ensino'] ?? 'Não';

    

    if (isset($_POST['nova_turma'])) {
        // Dividir o valor da turma em número e ano
        list($nova_turma_numero, $nova_turma_ano) = explode("|", $_POST['nova_turma']);
        $nova_turma_numero = intval($nova_turma_numero);
        $nova_turma_ano = intval($nova_turma_ano);
    
        // Atualizar a turma do discente
        $update_turma_query = "
            UPDATE discentes_turmas 
            SET turma_numero = ?, turma_ano = ?
            WHERE numero_matricula = ?
        ";
        $stmt_turma = $conn->prepare($update_turma_query);
        $stmt_turma->bind_param("iii", $nova_turma_numero, $nova_turma_ano, $matricula);
    
        if ($stmt_turma->execute()) {
            echo "<script>alert('Turma alterada com sucesso!');</script>";
        } else {
            echo "<script>alert('Erro ao alterar a turma.');</script>";
        }
    }
    

    // Atualizar os dados no banco de dados
    $update_query = "
    UPDATE discentes SET 
        acompanhamento = ?, apoio_psicologico = ?, auxilio_permanencia = ?, 
        cotista = ?, estagio = ?, acompanhamento_saude = ?, 
        projeto_pesquisa = ?, projeto_extensao = ?, projeto_ensino = ?
    WHERE numero_matricula = ?;
    ";

    $stmt = $conn->prepare($update_query);

    if (!$stmt) {
        die("Erro na preparação da query: " . $conn->error);
    }

    $stmt->bind_param(
        "sssssssssi", 
        $acompanhamento, $apoio_psicologico, $auxilio_permanencia, 
        $cotista, $estagio, $acompanhamento_saude, 
        $projeto_pesquisa, $projeto_extensao, $projeto_ensino, $matricula
    );

    if (!$stmt->execute()) {
        die("Erro ao executar a query: " . $stmt->error);
    }
     // Após salvar as alterações, redireciona para a página com os parâmetros da turma
     $turma_numero = $discente_info['turma_numero']; // Número da turma
     $turma_ano = $discente_info['turma_ano']; // Ano da turma
 
     // Redirecionamento para editar_discente.php com os parâmetros turma_numero e turma_ano
     header("Location: editar_discente.php");
     exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Discentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
    <style>
        h3{
            font-family: "Forum", "serif";
        }
        
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            
            <!-- Barra lateral -->
            <div class="col-md-3 sidebar">
                <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_setor.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button onclick="location.href='cadastrar_notas_globais.php'">
                    <i class="fas fa-th-list"></i> Cadastrar Notas Globais
                </button>
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-users"></i> Discentes
                </button>

                <!-- Menu expansível com Bootstrap -->
                <div id="expandable-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='cadastrar_discentes.php'">
                            <i class="fas fa-user-plus"></i> Cadastrar Discente
                        </button>
                    </div>
                    <div class="expandable-menu">
                        <button onclick="location.href='editar_discente.php'">
                            <i class="fas fa-user-edit"></i> Editar Discente
                        </button>
                    </div>
                </div>

                <button onclick="location.href='meu_perfil.php'">
                    <i class="fas fa-user"></i> Meu Perfil
                </button>
                <button class="btn btn-danger" onclick="location.href='sair.php'">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </button>
            </div>
            <!-- Conteúdo principal -->
            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Editar Discentes</div>
                            <div class="ms-auto d-flex align-items-center">
                                <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador">
                                <?php else: ?>
                                    <img src="imgs/setor-photo.png" alt="Foto do Setor">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>     
                </div>
                    
                <div class="container mt-4">                 
                    <div class="row">  
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-body"> 
                                <?php if ($displayTurmas): ?>
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar por Nome...">
                                            </div>
                                            <div class="col-md-3">
                                                <input type="text" id="filterTurma" class="form-control" placeholder="Filtrar por Turma...">
                                            </div>

                                            <div class="col-md-3">
                                                <input type="text" id="filterCurso" class="form-control" placeholder="Filtrar por Curso...">
                                            </div> 
                                        </div>


                                    <table  id="discentesTable" class="table table-bordered table-hover table-sm align-middle">
                                    <thead class="table-dark">
                                            <tr>
                                                <th>Discente</th>
                                                <th>Turma</th>
                                                <th>Curso</th>
                                                <th>Ação</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <p id="noResultsMessage" style="display: none; color: red;">Nenhum resultado encontrado</p> <!-- Mensagem -->
                                            <?php
                                            // Exibir discentes por turma e curso
                                            foreach ($turmas_por_curso as $curso_nome => $turmas): 
                                                foreach ($turmas as $turma):
                                                    $turma_numero = $turma['numero'];
                                                    $turma_ano = $turma['ano'];
                                                    $discentes_query = "
                                                        SELECT d.numero_matricula, d.nome AS discente_nome 
                                                        FROM discentes d
                                                        JOIN discentes_turmas dt ON d.numero_matricula = dt.numero_matricula
                                                        WHERE dt.turma_numero = ? AND dt.turma_ano = ?
                                                        ORDER BY d.nome;
                                                    ";
                                                    
                                                    $stmt = $conn->prepare($discentes_query);
                                                    $stmt->bind_param("ii", $turma_numero, $turma_ano);
                                                    $stmt->execute();
                                                    $result_discentes = $stmt->get_result();
                                                    while ($discente = $result_discentes->fetch_assoc()):
                                            ?>
                                                        <tr class="discente-row">
                                                        <td class="discente-nome"><?php echo htmlspecialchars($discente['discente_nome']); ?></td>
                                                        <td class="discente-turma"><?php echo htmlspecialchars($turma_numero) . " - " . htmlspecialchars($turma_ano); ?></td>
                                                        <td class="discente-curso"><?php echo htmlspecialchars($curso_nome); ?></td>
                                                            
                                                        <td class="text-center">
                                                            <button class="btn btn-warning bt-sm custom-btn" onclick="exibirInformacoes(<?php echo $discente['numero_matricula']; ?>)">
                                                                <i class="fas fa-edit" style="margin-right: 8px;"></i>
                                                                <span>Editar/Ver Detalhes</span>
                                                            </button>
                                                        </td>


                                                        </tr>
                                            <?php
                                                    endwhile;
                                                endforeach;
                                            endforeach;
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                                <!-- Seção de Informações do Discente -->
                                <?php if ($displayDiscenteInfo && isset($discente_info)): ?>
                                    <div class="col-12">
                                        
                                                <h3 class="card-title">Informações de <?php echo htmlspecialchars($discente_info['discente_nome']); ?></h3>
                                                <hr>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="matricula" value="<?php echo htmlspecialchars($discente_info['numero_matricula']); ?>">
                                                    
                                                    <!-- Primeira linha de tabelas -->
                                                    <div class="row mb-3">
                                                        <!-- Coluna da primeira tabela -->
                                                        <div class="col-md-6">
                                                            <div class="card-body">
                                                                <div class="table-container">
                                                                    <table class="table table-bordered">
                                                                        <tr>
                                                                            <td><strong>Matrícula:</strong></td>
                                                                            <td><input type="text" class="form-control" name="numero_matricula" value="<?php echo htmlspecialchars($discente_info['numero_matricula']); ?>" readonly></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Nome:</strong></td>
                                                                            <td><input type="text" class="form-control" name="nome" value="<?php echo htmlspecialchars($discente_info['discente_nome']); ?>" required></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Cidade:</strong></td>
                                                                            <td><input type="text" class="form-control" name="cidade" value="<?php echo htmlspecialchars($discente_info['cidade']); ?>"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>UF:</strong></td>
                                                                            <td><input type="text" class="form-control" name="uf" value="<?php echo htmlspecialchars($discente_info['uf']); ?>"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Email:</strong></td>
                                                                            <td><input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($discente_info['email']); ?>" required></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Gênero:</strong></td>
                                                                            <td><input type="text" class="form-control" name="genero" value="<?php echo htmlspecialchars($discente_info['genero']); ?>"></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Data de Nascimento:</strong></td>
                                                                            <td><input type="date" class="form-control" name="data_nascimento" value="<?php echo htmlspecialchars($discente_info['data_nascimento']); ?>" required></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td><strong>Observações:</strong></td>
                                                                            <td><input type="text" class="form-control" name="observacoes" value="<?php echo htmlspecialchars($discente_info['observacoes']); ?>" ></td>
                                                                        </tr>
                                                                        <tr>
                                                                        <td><strong>Reprovações?</strong></td>
                                                                        <td><input type="text" class="form-control" name="reprovacoes" value="<?php echo htmlspecialchars($discente_info['reprovacoes']); ?>" required></td>
                                                                        
                                                                        <tr>
                                                                    <td><strong>Turma:</strong></td>
                                                                    <td>
                                                                            <select class="form-select" name="nova_turma" id="turma">
                                                                                <option value="">Selecione a turma</option>
                                                                                <?php foreach ($turmas_por_curso as $curso_nome => $turmas): ?>
                                                                                    <optgroup label="<?php echo $curso_nome; ?>">
                                                                                        <?php foreach ($turmas as $turma): ?>
                                                                                            <?php
                                                                                                // Cria o valor do option com a combinação de número e ano da turma
                                                                                                $turma_value = $turma['numero'] . "|" . $turma['ano'];

                                                                                                // Verifica se a turma do discente já foi definida
                                                                                                $selected = '';
                                                                                                // Garantir que os dados do discente estejam presentes
                                                                                                if (isset($discente_info['turma_numero']) && isset($discente_info['turma_ano'])) {
                                                                                                    // Verifica se o número e ano da turma correspondem aos dados do discente
                                                                                                    $discente_turma_value = $discente_info['turma_numero'] . "|" . $discente_info['turma_ano'];
                                                                                                    if ($discente_turma_value == $turma_value) {
                                                                                                        $selected = 'selected'; // Marca como selecionada
                                                                                                    }
                                                                                                }
                                                                                            ?>
                                                                                            <option value="<?php echo $turma_value; ?>" <?php echo $selected; ?>>
                                                                                                <?php echo $turma['numero'] . ' - ' . $turma['ano']; ?>
                                                                                            </option>
                                                                                        <?php endforeach; ?>
                                                                                    </optgroup>
                                                                                <?php endforeach; ?>
                                                                            </select>
                                                                        </td>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Coluna da segunda tabela -->
                                                        <div class="col-md-6">
                                                            <div class="card-body">
                                                                <table class="table table-bordered">
                                                                    
                                                                <tr>
                                                                    <td><strong>Acompanhamento?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="acompanhamento">
                                                                            <option value="Sim" <?php echo ($discente_info['acompanhamento'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['acompanhamento'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Apoio Psicológico?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="apoio_psicologico">
                                                                            <option value="Sim" <?php echo ($discente_info['apoio_psicologico'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['apoio_psicologico'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Auxílio Permanência?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="auxilio_permanencia">
                                                                            <option value="Sim" <?php echo ($discente_info['auxilio_permanencia'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['auxilio_permanencia'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Cotista?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="cotista">
                                                                            <option value="Sim" <?php echo ($discente_info['cotista'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['cotista'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Estágio?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="estagio">
                                                                            <option value="Sim" <?php echo ($discente_info['estagio'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['estagio'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Acompanhamento de Saúde?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="acompanhamento_saude">
                                                                            <option value="Sim" <?php echo ($discente_info['acompanhamento_saude'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['acompanhamento_saude'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Projeto de Pesquisa?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="projeto_pesquisa">
                                                                            <option value="Sim" <?php echo ($discente_info['projeto_pesquisa'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['projeto_pesquisa'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Projeto de Extensão?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="projeto_extensao">
                                                                            <option value="Sim" <?php echo ($discente_info['projeto_extensao'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['projeto_extensao'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td><strong>Projeto de Ensino?</strong></td>
                                                                    <td>
                                                                        <select class="form-select" name="projeto_ensino">
                                                                            <option value="Sim" <?php echo ($discente_info['projeto_ensino'] == 'Sim' ? 'selected' : ''); ?>>Sim</option>
                                                                            <option value="Não" <?php echo ($discente_info['projeto_ensino'] == 'Não' ? 'selected' : ''); ?>>Não</option>
                                                                        </select>
                                                                    </td>
                                                                </tr>

                                                                </table>
                                                            </div>
                                                        </div>
                                                        
                                                        </div> <!-- Fim da primeira linha -->
                                                        
                                                        <div class="text-end mt-3">
                                                        <a href="editar_discente.php" class="btn btn-secondary btn-sm ms-2">
                                                            <i class="fas fa-arrow-left"></i> Voltar
                                                        </a>
                                                        <button type="submit" class="btn btn-success btn-block">
                                                            <i class="fas fa-save"></i> Salvar Alterações
                                                        </button>
                                                        
                                                    </div>


                                                    
                                                    </form>

                                                   
                                        <hr>
                                        <h3 class="mt-4">Notas de <?php echo htmlspecialchars($discente_info['discente_nome']); ?></h3>
                                            <table class="table table-bordered table-hover table-sm align-middle">
                                                <thead class="table-dark">
                                                    <tr>
                                                        <th>Disciplina</th>
                                                        <th>1º Parcial</th>
                                                        <th>AIS</th>
                                                        <th>1º Semestre</th>  
                                                        <th>2º Parcial</th>
                                                        <th>M.C.</th>
                                                        <th>PPI</th>
                                                        <th>2º Semestre</th>
                                                        <th>Nota Final</th>
                                                        <th>Exame</th>
                                                        <th>Faltas</th>
                                                        <th>Observações</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="notas-tabela">
                                                    <?php while ($nota = $result_notas->fetch_assoc()): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($nota['disciplina_nome']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['parcial_1']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['ais']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['nota_semestre_1']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['parcial_2']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['mostra_ciencias']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['ppi']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['nota_semestre_2']); ?></td>
                                                            <td class="nota-final"><?php echo htmlspecialchars($nota['nota_final']); ?></td>
                                                            <td class="nota-exame"><?php echo isset($nota['nota_exame']) ? htmlspecialchars($nota['nota_exame']) : 'N/A'; ?></td>
                                                            <td><?php echo htmlspecialchars($nota['faltas']); ?></td>
                                                            <td><?php echo htmlspecialchars($nota['observacoes']); ?></td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>

                                            <script>
                                                document.addEventListener("DOMContentLoaded", function() {
                                                    const linhas = document.querySelectorAll("#notas-tabela tr");

                                                    linhas.forEach(linha => {
                                                        const notaFinalCelula = linha.querySelector(".nota-final");
                                                        const notaExameCelula = linha.querySelector(".nota-exame");

                                                        if (notaFinalCelula && notaExameCelula) {
                                                            const notaFinal = parseFloat(notaFinalCelula.textContent);

                                                            if (!isNaN(notaFinal)) {
                                                                // Alterar a cor da "Nota Final" com base no valor
                                                                if (notaFinal >= 7) {
                                                                    notaFinalCelula.style.color = "green";
                                                                    notaExameCelula.textContent = "N/A"; // Ajustar o campo Exame
                                                                } else {
                                                                    notaFinalCelula.style.color = "red";
                                                                }
                                                            }
                                                        }
                                                    });
                                                });
                                            </script>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>                
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Captura os elementos de entrada de pesquisa
    const searchInput = document.getElementById('searchInput');
    const filterTurma = document.getElementById('filterTurma');
    const filterCurso = document.getElementById('filterCurso');  // Filtro de curso
    const tableRows = document.querySelectorAll('.discente-row'); // Todas as linhas da tabela
    const noResultsMessage = document.getElementById('noResultsMessage'); // Mensagem de "Nenhum resultado encontrado"

    // Função de filtragem
    function filterTable() {
        const searchValue = searchInput.value.toLowerCase();
        const turmaValue = filterTurma.value.toLowerCase(); // Filtro pela turma
        const cursoValue = filterCurso.value.toLowerCase(); // Filtro pelo curso

        let hasResults = false;

        // Percorre todas as linhas da tabela
        tableRows.forEach(row => {
            // Captura os valores das colunas relevantes
            const nomeDiscente = row.querySelector('.discente-nome').textContent.toLowerCase();
            const turma = row.querySelector('.discente-turma').textContent.split('-')[0].trim().toLowerCase(); // Apenas o número da turma
            const curso = row.querySelector('.discente-curso').textContent.toLowerCase(); // Curso do discente

            // Verifica se os valores digitados correspondem ao conteúdo das colunas
            const matchesSearch = !searchValue || nomeDiscente.includes(searchValue);
            const matchesTurma = !turmaValue || turma.includes(turmaValue); // Compara apenas o número da turma
            const matchesCurso = !cursoValue || curso.includes(cursoValue); // Verifica se o filtro de curso corresponde

            // Exibe ou oculta a linha com base na correspondência
            if (matchesSearch && matchesTurma && matchesCurso) {
                row.style.display = ""; // Mostra a linha
                hasResults = true;
            } else {
                row.style.display = "none"; // Oculta a linha
            }
        });

        // Exibe ou oculta a mensagem de "Nenhum resultado encontrado"
        noResultsMessage.style.display = hasResults ? "none" : "";
    }

    // Adiciona os eventos de digitação (input) nos campos de pesquisa
    searchInput.addEventListener('input', filterTable);
    filterTurma.addEventListener('input', filterTable); // Filtro apenas pelo número da turma
    filterCurso.addEventListener('input', filterTable); // Filtro pelo curso (mudança do valor)
});

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


<script>
// Função para listar os discentes de uma turma
function listarDiscentes(turma_numero, turma_ano) {
    window.location.href = `?turma_numero=${turma_numero}&turma_ano=${turma_ano}`;
}

// Função para exibir as informações do discente
function exibirInformacoes(matricula) {
    window.location.href = `?matricula=${matricula}`;
}
</script>
</body>
</html>
