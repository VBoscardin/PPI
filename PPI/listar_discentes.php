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

// Exibir os cursos e botões das turmas
if ($displayTurmas) {
    echo "<h1>Escolha a Turma para Verificar os Discentes</h1>";
    echo "<div style='display: flex; flex-wrap: wrap;'>";

    if ($result_cursos->num_rows > 0) {
        // Exibir cada curso como uma coluna
        while ($curso = $result_cursos->fetch_assoc()) {
            echo "<div style='flex: 1; margin: 10px;'>";  // Estilo flexível
            echo "<h3>" . htmlspecialchars($curso['nome']) . "</h3>";

            if (isset($turmas_por_curso[$curso['nome']])) {
                foreach ($turmas_por_curso[$curso['nome']] as $turma) {
                    $turma_numero = htmlspecialchars($turma['numero']);
                    $turma_ano = htmlspecialchars($turma['ano']);
                    echo "<button onclick='listarDiscentes($turma_numero, $turma_ano)'>Turma $turma_numero - Ano $turma_ano</button><br>";
                }
            }

            echo "</div>";
        }
    } else {
        echo "Nenhum curso encontrado.";
    }

    echo "</div>";
}

// Exibir os discentes quando uma turma for escolhida
if (!$displayTurmas && isset($result_discentes)) {
    echo "<h1>Discentes da Turma $turma_numero - Ano $turma_ano</h1>";
    echo "<div style='display: flex; flex-wrap: wrap;'>";

    while ($row = $result_discentes->fetch_assoc()) {
        $matricula = htmlspecialchars($row['numero_matricula']);
        $nome_discente = htmlspecialchars($row['discente_nome']);
        echo "<button onclick='exibirInformacoes($matricula)'>$nome_discente</button><br>";
    }

    echo "</div>";
}

// Exibir informações detalhadas do discente em tabela
if (!$displayTurmas && isset($discente_info)) {
    echo "<h1>Informações do Discente</h1>";
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    
    // Exibindo as informações pessoais do discente
    echo "<tr><td><strong>Matrícula:</strong></td><td>" . htmlspecialchars($discente_info['numero_matricula']) . "</td></tr>";
    echo "<tr><td><strong>Nome:</strong></td><td>" . htmlspecialchars($discente_info['discente_nome']) . "</td></tr>";
    echo "<tr><td><strong>Cidade:</strong></td><td>" . htmlspecialchars($discente_info['cidade']) . "</td></tr>";
    echo "<tr><td><strong>Email:</strong></td><td>" . htmlspecialchars($discente_info['email']) . "</td></tr>";
    echo "<tr><td><strong>Gênero:</strong></td><td>" . htmlspecialchars($discente_info['genero']) . "</td></tr>";
    echo "<tr><td><strong>Data de Nascimento:</strong></td><td>" . htmlspecialchars($discente_info['data_nascimento']) . "</td></tr>";
    echo "<tr><td><strong>Observações:</strong></td><td>" . htmlspecialchars($discente_info['observacoes']) . "</td></tr>";
    echo "<tr><td><strong>UF:</strong></td><td>" . htmlspecialchars($discente_info['uf']) . "</td></tr>";
    echo "<tr><td><strong>CPF:</strong></td><td>" . htmlspecialchars($discente_info['cpf']) . "</td></tr>";
    echo "<tr><td><strong>Reprovações:</strong></td><td>" . htmlspecialchars($discente_info['reprovacoes']) . "</td></tr>";
    echo "<tr><td><strong>Acompanhamento:</strong></td><td>" . htmlspecialchars($discente_info['acompanhamento']) . "</td></tr>";
    echo "<tr><td><strong>Apoio Psicológico:</strong></td><td>" . htmlspecialchars($discente_info['apoio_psicologico']) . "</td></tr>";
    echo "<tr><td><strong>Auxílio Permanência:</strong></td><td>" . htmlspecialchars($discente_info['auxilio_permanencia']) . "</td></tr>";
    echo "<tr><td><strong>Cotista:</strong></td><td>" . htmlspecialchars($discente_info['cotista']) . "</td></tr>";
    echo "<tr><td><strong>Estágio:</strong></td><td>" . htmlspecialchars($discente_info['estagio']) . "</td></tr>";
    echo "<tr><td><strong>Acompanhamento de Saúde:</strong></td><td>" . htmlspecialchars($discente_info['acompanhamento_saude']) . "</td></tr>";
    echo "<tr><td><strong>Projeto Pesquisa:</strong></td><td>" . htmlspecialchars($discente_info['projeto_pesquisa']) . "</td></tr>";
    echo "<tr><td><strong>Projeto Extensão:</strong></td><td>" . htmlspecialchars($discente_info['projeto_extensao']) . "</td></tr>";
    echo "<tr><td><strong>Projeto Ensino:</strong></td><td>" . htmlspecialchars($discente_info['projeto_ensino']) . "</td></tr>";
    echo "<tr><td><strong>Foto:</strong></td><td><img src='" . htmlspecialchars($discente_info['foto']) . "' alt='Foto do Discente' width='100'></td></tr>";
    echo "</table>";

    echo "<h2>Notas de " . htmlspecialchars($discente_info['discente_nome']) . "</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Disciplina</th><th>1º Parcial</th><th>1º Semestre</th><th>2º Parcial</th><th>2º Semestre</th><th>Nota Final</th><th>Exame</th><th>Faltas</th><th>Observações</th></tr>";

while ($nota = $result_notas->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($nota['disciplina_nome']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['parcial_1']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['nota_semestre_1']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['parcial_2']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['nota_semestre_2']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['nota_final']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['nota_exame']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['faltas']) . "</td>";
    echo "<td>" . htmlspecialchars($nota['observacoes']) . "</td>";  // Exibir as observações
    echo "</tr>";
}

echo "</table>";

}

// Fechar a conexão com o banco de dados
$conn->close();
?>

<script>
function listarDiscentes(turma_numero, turma_ano) {
    window.location.href = "?turma_numero=" + turma_numero + "&turma_ano=" + turma_ano;
}

function exibirInformacoes(matricula) {
    window.location.href = "?matricula=" + matricula;
}
</script>
