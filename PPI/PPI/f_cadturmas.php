<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleção de Turmas e Matérias</title>
</head>
<body>
    <h1>Selecione uma Turma e uma Matéria</h1>
    
    <form action="" method="POST">
        <label for="turma">Turmas Disponíveis:</label>
        <select name="turma" id="turma">
            <option value="turmaX">Turma X</option>
            <option value="turmaY">Turma Y</option>
        </select>
        <br><br>

        <label for="materia">Matérias Disponíveis:</label>
        <select name="materia" id="materia">
            <option value="matematica">Matemática</option>
            <option value="portugues">Português</option>
            <option value="historia">História</option>
            <option value="geografia">Geografia</option>
            <option value="ciencias">Ciências</option>
        </select>
        <br><br>

        <input type="submit" name="submit" value="Selecionar">
    </form>

    <?php
    if (isset($_POST['submit'])) {
        $turmaSelecionada = $_POST['turma'];
        $materiaSelecionada = $_POST['materia'];

        echo "<h2>Seleção Feita:</h2>";
        echo "Turma: " . ($turmaSelecionada == 'turmaX' ? "Turma X" : "Turma Y") . "<br>";
        echo "Matéria: ";
        
        switch ($materiaSelecionada) {
            case 'matematica':
                echo "Matemática";
                break;
            case 'portugues':
                echo "Português";
                break;
            case 'historia':
                echo "História";
                break;
            case 'geografia':
                echo "Geografia";
                break;
            case 'ciencias':
                echo "Ciências";
                break;
            default:
                echo "Nenhuma matéria foi selecionada.";
                break;
        }
    }
    ?>
</body>
</html>
