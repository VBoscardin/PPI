<?php
// Incluir as configurações e a conexão com o banco de dados
include_once 'config.php';



// 1. Exibir a lista de turmas
$query_turmas = "
    SELECT t.numero, t.ano, c.nome AS curso_nome
    FROM turmas t
    JOIN cursos c ON t.curso_id = c.id
    ORDER BY t.ano DESC, t.numero ASC
";
$result_turmas = $conn->query($query_turmas);

if ($result_turmas->num_rows > 0) {
    echo "<h1>Selecione uma Turma</h1>";
    echo "<ul>";

    // Exibir lista de turmas
    while ($turma = $result_turmas->fetch_assoc()) {
        echo "<li><a href='gerar_boletim.php?numero_turma=" . $turma['numero'] . "&ano_turma=" . $turma['ano'] . "'>Turma " . $turma['numero'] . " - " . $turma['curso_nome'] . " (" . $turma['ano'] . ")</a></li>";
    }

    echo "</ul>";
} else {
    echo "<p>Nenhuma turma cadastrada.</p>";
}

if (isset($_GET['numero_turma']) && isset($_GET['ano_turma'])) {
    // 2. Se uma turma foi selecionada, buscar os discentes dessa turma
    $numero_turma = $_GET['numero_turma'];
    $ano_turma = $_GET['ano_turma'];

    // Consultar os discentes associados a essa turma
    $query_discentes = "
        SELECT d.numero_matricula, d.nome, d.email, d.cidade
        FROM discentes_turmas dt
        JOIN discentes d ON dt.numero_matricula = d.numero_matricula
        WHERE dt.turma_numero = ? AND dt.turma_ano = ?
    ";
    $stmt_discentes = $conn->prepare($query_discentes);
    $stmt_discentes->bind_param('ii', $numero_turma, $ano_turma);
    $stmt_discentes->execute();
    $result_discentes = $stmt_discentes->get_result();

    if ($result_discentes->num_rows > 0) {
        echo "<h2>Discentes da Turma " . $numero_turma . " - Ano " . $ano_turma . "</h2>";
        echo "<table border='1'>";
        echo "<thead><tr><th>Nome</th><th>Email</th><th>Cidade</th><th>Ação</th></tr></thead>";
        echo "<tbody>";

        // Exibir a lista de discentes da turma
        while ($discente = $result_discentes->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($discente['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($discente['email']) . "</td>";
            echo "<td>" . htmlspecialchars($discente['cidade']) . "</td>";
            echo "<td><a href='gerar_boletim.php?numero_matricula=" . $discente['numero_matricula'] . "'>Ver Boletim</a></td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    } else {
        echo "<p>Não há discentes cadastrados para essa turma.</p>";
    }

    $stmt_discentes->close();
}

if (isset($_GET['numero_matricula'])) {
    // 3. Se um discente for selecionado, mostrar o boletim dele
    $numero_matricula = $_GET['numero_matricula'];

    // Consultar as notas do discente
    $query_boletim = "
        SELECT n.nota_final, n.nota_exame, n.parcial_1, n.nota_semestre_1, n.parcial_2, n.nota_semestre_2, 
               n.faltas, n.observacoes, d.nome AS disciplina_nome
        FROM notas n
        JOIN disciplinas d ON n.disciplina_id = d.id
        WHERE n.discente_id = ?
    ";
    $stmt_boletim = $conn->prepare($query_boletim);
    $stmt_boletim->bind_param('i', $numero_matricula);
    $stmt_boletim->execute();
    $result_boletim = $stmt_boletim->get_result();

    if ($result_boletim->num_rows > 0) {
        echo "<h2>Boletim do Discente (Matrícula: " . $numero_matricula . ")</h2>";
        echo "<table border='1'>";
        echo "<thead><tr><th>Disciplina</th><th>Parcial 1</th><th>Nota Semestre 1</th><th>Parcial 2</th><th>Nota Semestre 2</th><th>Nota Final</th><th>Nota Exame</th><th>Faltas</th><th>Observações</th></tr></thead>";
        echo "<tbody>";

        // Exibir as notas do discente
        while ($boletim = $result_boletim->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($boletim['disciplina_nome']) . "</td>";
            echo "<td>" . $boletim['parcial_1'] . "</td>";
            echo "<td>" . $boletim['nota_semestre_1'] . "</td>";
            echo "<td>" . $boletim['parcial_2'] . "</td>";
            echo "<td>" . $boletim['nota_semestre_2'] . "</td>";
            echo "<td>" . ($boletim['nota_final'] !== null ? $boletim['nota_final'] : 'N/A') . "</td>";
            echo "<td>" . ($boletim['nota_exame'] !== null ? $boletim['nota_exame'] : 'N/A') . "</td>";
            echo "<td>" . $boletim['faltas'] . "</td>";
            echo "<td>" . htmlspecialchars($boletim['observacoes']) . "</td>";
            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";

        // Botão para baixar o boletim em PDF
        echo "<br><a href='gerar_boletim.php?numero_matricula=" . $numero_matricula . "&download_pdf=true' target='_blank'>Baixar PDF</a>";
    } else {
        echo "<p>Não há boletim disponível para esse discente.</p>";
    }

    $stmt_boletim->close();
}

// Gerar o PDF caso o parâmetro 'download_pdf' esteja presente
if (isset($_GET['download_pdf']) && $_GET['download_pdf'] === 'true' && isset($_GET['numero_matricula'])) {
    $numero_matricula = $_GET['numero_matricula'];

    // Consultar as notas do discente
    $query_boletim_pdf = "
        SELECT n.nota_final, n.nota_exame, n.parcial_1, n.nota_semestre_1, n.parcial_2, n.nota_semestre_2, 
               n.faltas, n.observacoes, d.nome AS disciplina_nome
        FROM notas n
        JOIN disciplinas d ON n.disciplina_id = d.id
        WHERE n.discente_id = ?
    ";
    $stmt_boletim_pdf = $conn->prepare($query_boletim_pdf);
    $stmt_boletim_pdf->bind_param('i', $numero_matricula);
    $stmt_boletim_pdf->execute();
    $result_boletim_pdf = $stmt_boletim_pdf->get_result();

    // Criar o PDF
    $pdf = new TCPDF();
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', '', 12);

    $pdf->Cell(0, 10, 'Boletim do Discente', 0, 1, 'C');
    $pdf->Ln(5);

    while ($boletim = $result_boletim_pdf->fetch_assoc()) {
        $pdf->Cell(0, 10, 'Disciplina: ' . $boletim['disciplina_nome'], 0, 1);
        $pdf->Cell(0, 10, 'Parcial 1: ' . $boletim['parcial_1'], 0, 1);
        $pdf->Cell(0, 10, 'Nota Semestre 1: ' . $boletim['nota_semestre_1'], 0, 1);
        $pdf->Cell(0, 10, 'Parcial 2: ' . $boletim['parcial_2'], 0, 1);
        $pdf->Cell(0, 10, 'Nota Semestre 2: ' . $boletim['nota_semestre_2'], 0, 1);
        $pdf->Cell(0, 10, 'Nota Final: ' . ($boletim['nota_final'] !== null ? $boletim['nota_final'] : 'N/A'), 0, 1);
        $pdf->Cell(0, 10, 'Nota Exame: ' . ($boletim['nota_exame'] !== null ? $boletim['nota_exame'] : 'N/A'), 0, 1);
        $pdf->Cell(0, 10, 'Faltas: ' . $boletim['faltas'], 0, 1);
        $pdf->Cell(0, 10, 'Observações: ' . $boletim['observacoes'], 0, 1);
        $pdf->Ln(5);
    }

    $stmt_boletim_pdf->close();

    // Finalizar e gerar o PDF
    $pdf->Output('boletim_' . $numero_matricula . '.pdf', 'D');  // 'D' significa Download
    exit;  // Evitar que o restante do HTML seja exibido após o PDF ser gerado
}

$conn->close();
?>
