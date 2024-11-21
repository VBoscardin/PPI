<?php
session_start();

// Verificar se o usuário está autenticado
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type'])) {
    header("Location: f_login.php");
    exit();
}

// Verificar se o usuário tem permissão para acessar esta página (exemplo: administrador)
if ($_SESSION['user_type'] !== 'administrador') {
    header("Location: f_login.php");
    exit();
}

include 'config.php';

// Verificar a conexão com o banco de dados
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obter o nome e a foto do perfil do administrador
$stmt = $conn->prepare("SELECT username, foto_perfil FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$stmt->bind_result($nome, $foto_perfil);
$stmt->fetch();
$stmt->close();

// Flag para controlar qual parte da página exibir
$displayTurmas = true;

// Verificar se os parâmetros de turma foram passados na URL
if (isset($_GET['turma_numero']) && isset($_GET['turma_ano'])) {
    $turma_numero = intval($_GET['turma_numero']);
    $turma_ano = intval($_GET['turma_ano']);
    $displayTurmas = false; // Não exibir turmas, exibir discentes
    
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

if (isset($_GET['matricula'])) {
    $matricula = intval($_GET['matricula']);

    // Consultar as informações do discente com as novas colunas
    $query_discente = "
        SELECT 
            d.numero_matricula, d.nome AS discente_nome, 
            d.cidade, d.email, d.genero, d.data_nascimento, d.observacoes,
            d.uf, d.cpf, d.reprovacoes, d.acompanhamento, 
            d.apoio_psicologico, d.auxilio_permanencia, d.cotista, 
            d.estagio, d.acompanhamento_saude, d.projeto_pesquisa, 
            d.projeto_extensao, d.projeto_ensino, d.foto
        FROM discentes d
        WHERE d.numero_matricula = ?
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
        n.parcial_1, n.nota_semestre_1, n.parcial_2, n.nota_semestre_2,
        n.nota_final, n.nota_exame, n.faltas, n.observacoes
    FROM notas n
    JOIN disciplinas di ON n.disciplina_id = di.id
    WHERE n.discente_id = ? 
    ORDER BY di.nome;
    ";

    $stmt = $conn->prepare($query_notas);
    $stmt->bind_param("i", $matricula);
    $stmt->execute();
    $result_notas = $stmt->get_result();

    $displayTurmas = false; // Exibir as informações do discente, não as turmas
}

// Consultar os cursos e as turmas
$query_cursos = "SELECT id, nome FROM cursos";
$result_cursos = $conn->query($query_cursos);

// Consultar as turmas por curso
$query_turmas = "
    SELECT t.numero, t.ano, t.curso_id, c.nome AS curso_nome
    FROM turmas t
    JOIN cursos c ON t.curso_id = c.id
    ORDER BY c.nome, t.numero
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

// Fechar a conexão com o banco de dados
$conn->close();

?>

<!-- Aqui começa o HTML -->
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Discentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Forum:wght@700&display=swap" rel="stylesheet">
    <link href="style.css" rel="stylesheet" type="text/css">
    <style>
        h3{
            font-family: "Forum", "serif";
        }
        
    </style>
</head>

<body>
<body>
    
    <div class="container-fluid">
        <div class="row">
        <div class="col-md-3 sidebar">
            <div class="separator mb-3"></div>
                <div class="signe-text">SIGNE</div>
                <div class="separator mt-3 mb-3"></div>
                <button onclick="location.href='f_pagina_adm.php'">
                    <i class="fas fa-home"></i> Início
                </button>
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#expandable-menu" aria-expanded="false" aria-controls="expandable-menu">
                    <i id="toggle-icon" class="fas fa-plus"></i> Cadastrar
                </button>
                <div id="expandable-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='cadastrar_adm.php'">
                            <i class="fas fa-plus"></i> Cadastrar Administrador
                        </button>
                        <button onclick="location.href='cadastrar_curso.php'">
                            <i class="fas fa-plus"></i> Cadastrar Curso
                        </button>
                        <button onclick="location.href='cadastrar_disciplina.php'">
                            <i class="fas fa-plus"></i> Cadastrar Disciplina
                        </button>
                        <button onclick="location.href='cadastrar_docente.php'">
                            <i class="fas fa-plus"></i> Cadastrar Docente
                        </button>
                        <button onclick="location.href='cadastrar_setor.php'">
                            <i class="fas fa-plus"></i> Cadastrar Setor
                        </button>
                        <button onclick="location.href='cadastrar_turma.php'">
                            <i class="fas fa-plus"></i> Cadastrar Turma
                        </button>
                    </div>
                </div>
                <button onclick="location.href='gerar_boletim.php'">
                    <i class="fas fa-file-alt"></i> Gerar Boletim
                </button>
                <button onclick="location.href='gerar_slide.php'">
                    <i class="fas fa-sliders-h"></i> Gerar Slide Pré Conselho
                </button>
                
                <button class="btn btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#list-menu" aria-expanded="false" aria-controls="list-menu">
                    <i id="toggle-icon" class="fas fa-list"></i> Listar
                </button>

                <div id="list-menu" class="collapse expandable-container">
                    <div class="expandable-menu">
                        <button onclick="location.href='listar_administradores.php'">
                            <i class="fas fa-list"></i> Administradores
                        </button>
                        <button onclick="location.href='listar_cursos.php'">
                            <i class="fas fa-list"></i> Cursos
                        </button>
                        <button onclick="location.href='listar_discentes.php'">
                            <i class="fas fa-list"></i> Discentes
                        </button>
                        <button onclick="location.href='listar_disciplinas.php'">
                            <i class="fas fa-list"></i> Disciplinas
                        </button>
                        <button onclick="location.href='listar_docentes.php'">
                            <i class="fas fa-list"></i> Docentes
                        </button>
                        <button onclick="location.href='listar_setores.php'">
                            <i class="fas fa-list"></i> Setores
                        </button>
                        <button onclick="location.href='listar_turmas.php'">
                            <i class="fas fa-list"></i> Turmas
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
            <div class="col-md-9 main-content">
                <div class="container">
                    <div class="header-container">
                        <img src="imgs/iffar.png" alt="Logo do IFFAR" class="logo">
                        <div class="title ms-3">Listar e Editar Discentes</div>
                        <div class="ms-auto d-flex align-items-center">
                            <div class="profile-info d-flex align-items-center">
                                <div class="profile-details me-2">
                                    <span><?php echo htmlspecialchars($nome); ?></span>
                                </div>
                                <?php if (!empty($foto_perfil) && file_exists('uploads/' . basename($foto_perfil))): ?>
                                    <img src="uploads/<?php echo htmlspecialchars(basename($foto_perfil)); ?>" alt="Foto do Administrador" width="50">
                                <?php else: ?>
                                    <img src="imgs/admin-photo.png" alt="Foto do Administrador" width="50">
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Melhorando a disposição das informações -->
                <div class="container mt-4">
                    <!-- Exibir mensagens de sucesso e erro -->
                    <?php if (isset($_SESSION['mensagem'])): ?>
                        <div class="alert alert-success" role="alert">
                            <?php
                                echo htmlspecialchars($_SESSION['mensagem']);
                                unset($_SESSION['mensagem']); // Limpar a mensagem após exibição
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($erro)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php
                                echo htmlspecialchars($erro);
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Seção de Escolher Turma -->
                        <?php if ($displayTurmas): ?>
                        <div class="col-12">
                            <div class="card shadow mb-4">
                                <div class="card-body">
                                    <h3 class="card-title">Escolha a Turma para Verificar os Discentes</h3>
                                    <hr>
                                    <div class="row">
                                        <?php if ($result_cursos->num_rows > 0): ?>
                                            <?php while ($curso = $result_cursos->fetch_assoc()): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="card text-center">
                                                        <div class="card-header">
                                                            <h4 class="card-title"><?php echo htmlspecialchars($curso['nome']); ?></h4>
                                                        </div>
                                                        <div class="card-body">
                                                            <?php if (isset($turmas_por_curso[$curso['nome']])): ?>
                                                                <?php foreach ($turmas_por_curso[$curso['nome']] as $turma): ?>
                                                                    <button class="btn btn-success mb-2" onclick='listarDiscentes(<?php echo htmlspecialchars($turma['numero']); ?>, <?php echo htmlspecialchars($turma['ano']); ?>)'>
                                                                        Turma <?php echo htmlspecialchars($turma['numero']); ?> - Ano <?php echo htmlspecialchars($turma['ano']); ?>
                                                                    </button><br>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p>Nenhum curso encontrado.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Seção de Discentes -->
                        <?php if (!$displayTurmas && isset($result_discentes)): ?>
                        <div class="col-12">
                            <div class="card shadow mb-4">
                            <div class="card-body">
                    <h3 class="card-title mb-4">Discentes da Turma <?php echo htmlspecialchars($turma_numero); ?> - Ano <?php echo htmlspecialchars($turma_ano); ?></h3>
                    <hr>
                    <?php if ($result_discentes->num_rows > 0): ?>
                        <!-- Início da lista de discentes -->
                        <div class="row">
                            <?php while ($row = $result_discentes->fetch_assoc()): ?>
                                <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($row['discente_nome']); ?></h5>
                                            <button class="btn btn-primary btn-block" onclick='exibirInformacoes(<?php echo htmlspecialchars($row['numero_matricula']); ?>)'>
                                                Ver Informações
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <!-- Fim da lista de discentes -->
                    <?php else: ?>
                        <!-- Mensagem caso não existam discentes -->
                        <!-- Botão de Voltar para a Turma -->
                        <button class="btn btn-primary mb-4" onclick="window.location.href='listar_discentes.php';">
                                        <i class="fas fa-arrow-left"></i> Voltar para a lista de turmas
                                    </button>
                        <p class="text-center">Não há discentes cadastrados para esta turma.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endif; ?>

        <!-- Seção de Informações do Discente -->
        <?php if (!$displayTurmas && isset($discente_info)): ?>
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h3 class="card-title">Informações de <?php echo htmlspecialchars($discente_info['discente_nome']); ?></h3>
                    <hr>
                    <!-- Botão de Voltar para a Turma -->
                    <button class="btn btn-primary mb-4" onclick="voltarParaTurma()">
                        <i class="fas fa-arrow-left"></i> Voltar para a Turma
                    </button>
                    <hr>
                    <table class="table table-bordered table-hover table-sm" style="border-radius: 4px; overflow: hidden;">
                                <thead class="table-dark">
                        <tbody>
                            <tr><td><strong>Nome:</strong></td><td><?php echo htmlspecialchars($discente_info['discente_nome']); ?></td></tr>
                            <tr><td><strong>Matrícula:</strong></td><td><?php echo htmlspecialchars($discente_info['numero_matricula']); ?></td></tr>
                            <tr><td><strong>Cidade:</strong></td><td><?php echo htmlspecialchars($discente_info['cidade']); ?></td></tr>
                            <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($discente_info['email']); ?></td></tr>
                            <tr><td><strong>Gênero:</strong></td><td><?php echo htmlspecialchars($discente_info['genero']); ?></td></tr>
                            <tr><td><strong>Data de Nascimento:</strong></td><td><?php echo htmlspecialchars($discente_info['data_nascimento']); ?></td></tr>
                            <tr><td><strong>Observações:</strong></td><td><?php echo htmlspecialchars($discente_info['observacoes']); ?></td></tr>
                            <tr><td><strong>UF:</strong></td><td><?php echo htmlspecialchars($discente_info['uf']); ?></td></tr>
                            <tr><td><strong>CPF:</strong></td><td><?php echo htmlspecialchars($discente_info['cpf']); ?></td></tr>
                            <tr><td><strong>Reprovações:</strong></td><td><?php echo htmlspecialchars($discente_info['reprovacoes']); ?></td></tr>
                            <tr><td><strong>Acompanhamento:</strong></td><td><?php echo (htmlspecialchars($discente_info['acompanhamento']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Apoio Psicológico:</strong></td><td><?php echo (htmlspecialchars($discente_info['apoio_psicologico']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Auxílio Permanência:</strong></td><td><?php echo (htmlspecialchars($discente_info['auxilio_permanencia']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Cotista:</strong></td><td><?php echo (htmlspecialchars($discente_info['cotista']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Estágio:</strong></td><td><?php echo (htmlspecialchars($discente_info['estagio']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Acompanhamento de Saúde:</strong></td><td><?php echo (htmlspecialchars($discente_info['acompanhamento_saude']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Projeto Pesquisa:</strong></td><td><?php echo (htmlspecialchars($discente_info['projeto_pesquisa']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Projeto Extensão:</strong></td><td><?php echo (htmlspecialchars($discente_info['projeto_extensao']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Projeto Ensino:</strong></td><td><?php echo (htmlspecialchars($discente_info['projeto_ensino']) ? 'Sim' : 'Não'); ?></td></tr>
                            <tr><td><strong>Foto:</strong></td><td><img src="<?php echo htmlspecialchars($discente_info['foto']); ?>" alt="Foto do Discente" width="100"></td></tr>
                        </tbody>
                    </table>
                    <br>
                    <hr>
                    <h3 class="mt-4">Notas de <?php echo htmlspecialchars($discente_info['discente_nome']); ?></h3>
                    <table class="table table-bordered table-hover table-sm" style="border-radius: 4px; overflow: hidden;">
                                <thead class="table-dark">
                            <tr>
                                <th>Disciplina</th>
                                <th>1º Parcial</th>
                                <th>1º Semestre</th>
                                <th>2º Parcial</th>
                                <th>2º Semestre</th>
                                <th>Nota Final</th>
                                <th>Exame</th>
                                <th>Faltas</th>
                                <th>Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($nota = $result_notas->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($nota['disciplina_nome']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['parcial_1']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['nota_semestre_1']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['parcial_2']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['nota_semestre_2']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['nota_final']); ?></td>
                                    <td><?php echo isset($nota['exame']) ? htmlspecialchars($nota['exame']) : 'N/A'; ?></td>
                                    <td><?php echo htmlspecialchars($nota['faltas']); ?></td>
                                    <td><?php echo htmlspecialchars($nota['observacoes']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>


<script>
function listarDiscentes(turma_numero, turma_ano) {
    window.location.href = "?turma_numero=" + turma_numero + "&turma_ano=" + turma_ano;
}

function exibirInformacoes(matricula) {
    window.location.href = "?matricula=" + matricula;
}

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function voltarParaTurma() {
    // Obtém os parâmetros da URL
    const urlParams = new URLSearchParams(window.location.search);
    const turma_numero = urlParams.get('turma_numero');
    const turma_ano = urlParams.get('turma_ano');
   
    // Verifica se os parâmetros existem e redireciona
    if (turma_numero && turma_ano) {
        window.location.href = "?turma_numero=" + turma_numero + "&turma_ano=" + turma_ano;
    } else {
        // Caso não tenha os parâmetros na URL, redireciona para uma página padrão
        window.location.href = "listar_discentes.php"; // Ajuste para a página inicial ou padrão que você desejar
    }
}

</script>

</body>
</html>
