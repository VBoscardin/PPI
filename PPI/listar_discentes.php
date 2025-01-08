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
            d.uf, d.reprovacoes, d.acompanhamento, 
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
    
    #discentesTable td {
        background-color: white; /* Sem aspas no valor */
    }
    #discentesNota td {
        background-color: white; /* Sem aspas no valor */
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
                        <div class="title ms-3">Listar Discentes</div>
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
                        
                    <!-- Exibir todos os discentes agrupados por curso -->
    <?php if ($displayTurmas): ?>
    <div class="col-12">
    <div class="card shadow mb-4">
        <div class="card-body">
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
                                        <button class="btn btn-info btn-sm" onclick="exibirInformacoes(<?php echo $discente['numero_matricula']; ?>)">
                                            <i class="fas fa-eye" style="margin-right: 8px;"></i>
                                            <span>Ver Detalhes</span>
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
                    <table class="table table-bordered table-hover table-sm align-middle">
                        <thead class="table-dark">
                        <tbody>
                            <tr><td><strong>Nome:</strong></td><td><?php echo htmlspecialchars($discente_info['discente_nome']); ?></td></tr>
                            <tr><td><strong>Matrícula:</strong></td><td><?php echo htmlspecialchars($discente_info['numero_matricula']); ?></td></tr>
                            <tr><td><strong>Cidade:</strong></td><td><?php echo htmlspecialchars($discente_info['cidade']); ?></td></tr>
                            <tr><td><strong>Email:</strong></td><td><?php echo htmlspecialchars($discente_info['email']); ?></td></tr>
                            <tr><td><strong>Gênero:</strong></td><td><?php echo htmlspecialchars($discente_info['genero']); ?></td></tr>
                            <tr><td><strong>Data de Nascimento:</strong></td>
                            <td>
                                <?php 
                                    $data_nascimento = new DateTime($discente_info['data_nascimento']);
                                    echo htmlspecialchars($data_nascimento->format('d/m/Y')); 
                                ?>
                            </td></tr>
                            <tr><td><strong>Observações:</strong></td><td><?php echo htmlspecialchars($discente_info['observacoes']); ?></td></tr>
                            <tr><td><strong>UF:</strong></td><td><?php echo htmlspecialchars($discente_info['uf']); ?></td></tr>
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
                        <table id="discentesNota" class="table table-bordered table-hover table-sm align-middle">
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
        <?php endif; ?>
    </div>
</div>


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
