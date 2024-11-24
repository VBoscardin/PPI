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
            d.uf, d.cpf, d.reprovacoes, d.acompanhamento, 
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
    $email = $_POST['email'];
    $genero = $_POST['genero'];
    $data_nascimento = $_POST['data_nascimento'];
    $observacoes = $_POST['observacoes'];

    // Valores de "Sim" ou "Não"
    $reprovacoes = isset($_POST['reprovacoes']) && $_POST['reprovacoes'] === 'Sim' ? 1 : 0;
    $acompanhamento = isset($_POST['acompanhamento']) && $_POST['acompanhamento'] === 'Sim' ? 1 : 0;
    $apoio_psicologico = isset($_POST['apoio_psicologico']) && $_POST['apoio_psicologico'] === 'Sim' ? 1 : 0;
    $auxilio_permanencia = isset($_POST['auxilio_permanencia']) && $_POST['auxilio_permanencia'] === 'Sim' ? 1 : 0;
    $cotista = isset($_POST['cotista']) && $_POST['cotista'] === 'Sim' ? 1 : 0;
    $estagio = isset($_POST['estagio']) && $_POST['estagio'] === 'Sim' ? 1 : 0;
    $acompanhamento_saude = isset($_POST['acompanhamento_saude']) && $_POST['acompanhamento_saude'] === 'Sim' ? 1 : 0;
    $projeto_pesquisa = isset($_POST['projeto_pesquisa']) && $_POST['projeto_pesquisa'] === 'Sim' ? 1 : 0;
    $projeto_extensao = isset($_POST['projeto_extensao']) && $_POST['projeto_extensao'] === 'Sim' ? 1 : 0;
    $projeto_ensino = isset($_POST['projeto_ensino']) && $_POST['projeto_ensino'] === 'Sim' ? 1 : 0;

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
            nome = ?, cidade = ?, email = ?, genero = ?, data_nascimento = ?, observacoes = ?, 
            reprovacoes = ?, acompanhamento = ?, apoio_psicologico = ?, auxilio_permanencia = ?, 
            cotista = ?, estagio = ?, acompanhamento_saude = ?, projeto_pesquisa = ?, 
            projeto_extensao = ?, projeto_ensino = ?
        WHERE numero_matricula = ?;
    ";

    // Preparar e executar a consulta
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssssiiiiiiiiiis", $nome, $cidade, $email, $genero, $data_nascimento, $observacoes, 
                      $reprovacoes, $acompanhamento, $apoio_psicologico, $auxilio_permanencia, 
                      $cotista, $estagio, $acompanhamento_saude, $projeto_pesquisa, $projeto_extensao, 
                      $projeto_ensino, $matricula);
    
                      if ($stmt->execute()) {
                        // Verificar se os parâmetros "turma_numero" e "turma_ano" estão na URL
                        if (isset($_GET['turma_numero']) && isset($_GET['turma_ano'])) {
                            $turma_numero = $_GET['turma_numero'];  // Pega o número da turma
                            $turma_ano = $_GET['turma_ano'];  // Pega o ano da turma
                
                            // Redirecionar para a mesma página com os parâmetros da URL
                            header("Location: " . $_SERVER['PHP_SELF'] . "?turma_numero=$turma_numero&turma_ano=$turma_ano");
                            exit();  // Para garantir que o script pare aqui após o redirecionamento
                        } else {
                            // Se não houver os parâmetros, redireciona para a página padrão
                            header("Location: editar_discente.php");
                            exit();
                        }
                    } else {
                        echo "<script>alert('Erro ao atualizar as informações.');</script>";
                    }
                }




// Exibir os cursos e botões para turmas
if ($displayTurmas) {
    echo "<h1>Escolha a Turma para Verificar os Discentes</h1>";
    echo "<div style='display: flex; flex-wrap: wrap;'>";

    if ($result_cursos->num_rows > 0) {
        // Exibir cada curso com suas turmas
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
if ($displayDiscenteInfo && isset($discente_info)) {
    echo "<h1>Informações do Discente</h1>";
    echo "<form method='POST' action=''>";

    echo "<input type='hidden' name='matricula' value='" . htmlspecialchars($discente_info['numero_matricula']) . "'>";

    // Exibindo as informações pessoais do discente
    echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><td><strong>Matrícula:</strong></td><td><input type='text' name='numero_matricula' value='" . htmlspecialchars($discente_info['numero_matricula']) . "' readonly></td></tr>";
    echo "<tr><td><strong>Nome:</strong></td><td><input type='text' name='nome' value='" . htmlspecialchars($discente_info['discente_nome']) . "' required></td></tr>";
    echo "<tr><td><strong>Cidade:</strong></td><td><input type='text' name='cidade' value='" . htmlspecialchars($discente_info['cidade']) . "'></td></tr>";
    echo "<tr><td><strong>Email:</strong></td><td><input type='email' name='email' value='" . htmlspecialchars($discente_info['email']) . "' required></td></tr>";
    echo "<tr><td><strong>Gênero:</strong></td><td><input type='text' name='genero' value='" . htmlspecialchars($discente_info['genero']) . "'></td></tr>";
    echo "<tr><td><strong>Data de Nascimento:</strong></td><td><input type='date' name='data_nascimento' value='" . htmlspecialchars($discente_info['data_nascimento']) . "' required></td></tr>";
    echo "<tr><td><strong>Observações:</strong></td><td><textarea name='observacoes'>" . htmlspecialchars($discente_info['observacoes']) . "</textarea></td></tr>";
    
// Consultar todas as turmas disponíveis
$query_turmas_disponiveis = "SELECT numero, ano, curso_id FROM turmas";
$result_turmas_disponiveis = $conn->query($query_turmas_disponiveis);

// Adicione o campo de seleção de turma no formulário
echo "<tr><td><strong>Turma:</strong></td><td><select name='nova_turma'>";
while ($turma = $result_turmas_disponiveis->fetch_assoc()) {
    $turma_identificacao = "Turma " . $turma['numero'] . " - Ano " . $turma['ano'];
    $turma_value = $turma['numero'] . "|" . $turma['ano']; // Combinação de número e ano
    $selected = ($turma['numero'] == $discente_info['numero_turma'] && $turma['ano'] == $discente_info['ano_turma']) ? "selected" : ""; // Verifica se é a turma atual
    echo "<option value='$turma_value' $selected>$turma_identificacao</option>";
}
echo "</select></td></tr>";

    // Exibindo as opções de "Sim" ou "Não"
    $opcoes = [
        'reprovacoes' => 'Reprovado?',
        'acompanhamento' => 'Acompanhamento?',
        'apoio_psicologico' => 'Apoio Psicológico?',
        'auxilio_permanencia' => 'Auxílio Permanência?',
        'cotista' => 'Cotista?',
        'estagio' => 'Estágio?',
        'acompanhamento_saude' => 'Acompanhamento de Saúde?',
        'projeto_pesquisa' => 'Projeto de Pesquisa?',
        'projeto_extensao' => 'Projeto de Extensão?',
        'projeto_ensino' => 'Projeto de Ensino?'
    ];

    foreach ($opcoes as $campo => $label) {
        $valor = $discente_info[$campo] == 1 ? 'Sim' : 'Não';
        echo "<tr><td><strong>$label</strong></td><td><select name='$campo'>
                <option value='Sim' " . ($valor == 'Sim' ? 'selected' : '') . ">Sim</option>
                <option value='Não' " . ($valor == 'Não' ? 'selected' : '') . ">Não</option>
              </select></td></tr>";
    }

    echo "</table>";

    // Exibir as notas
    if ($result_notas->num_rows > 0) {
        echo "<h2>Notas</h2>";
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Disciplina</th><th>Parcial 1</th><th>Nota Semestre 1</th><th>Parcial 2</th><th>Nota Semestre 2</th><th>Nota Final</th><th>Nota Exame</th><th>Faltas</th><th>Observações</th></tr>";

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
            echo "<td>" . htmlspecialchars($nota['observacoes']) . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    }

    echo "<button type='submit'>Salvar Alterações</button>";
    echo "</form>";
}

$conn->close();
?>

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
