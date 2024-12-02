
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `tipo` ENUM('administrador', 'docente', 'setor') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `cursos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `coordenador` VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



CREATE TABLE `disciplinas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `curso_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `setores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `local` varchar(100) NOT NULL,
  `nome` varchar(100) NOT NULL,
  
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
  
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `siape` varchar(14) NOT NULL UNIQUE,
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `docentes_disciplinas` (
  `docente_id` int(11) NOT NULL,
  `disciplina_id` int(11) NOT NULL,
  FOREIGN KEY (`docente_id`) REFERENCES `docentes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas`(`id`) ON DELETE CASCADE,
  PRIMARY KEY (`docente_id`, `disciplina_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `turmas` (
  `numero` INT NOT NULL,
  `ano` YEAR NOT NULL,
  `ano_ingresso` YEAR NOT NULL,
  `ano_oferta` YEAR NOT NULL,
  `professor_regente` INT NULL,
  `curso_id` INT NOT NULL,
  PRIMARY KEY (`numero`),
  FOREIGN KEY (`professor_regente`) REFERENCES `docentes` (`id`),
  FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;




CREATE TABLE `turmas_disciplinas` (
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,
  `turma_ano_ingresso` YEAR NOT NULL,
  `disciplina_id` INT NOT NULL,
  FOREIGN KEY (`turma_numero`) REFERENCES `turmas` (`numero`),
  FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE setores
ADD COLUMN email VARCHAR(100) NOT NULL;

CREATE TABLE discentes (
    numero_matricula INT(11) NOT NULL AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    genero ENUM('Masculino', 'Feminino', 'Outro') NOT NULL,
    data_nascimento DATE NOT NULL,
    observacoes TEXT DEFAULT NULL,
    PRIMARY KEY (numero_matricula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE discentes
ADD COLUMN uf VARCHAR(2) NOT NULL,

ADD COLUMN reprovacoes INT(11) DEFAULT 0,
ADD COLUMN acompanhamento ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN apoio_psicologico ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN auxilio_permanencia ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN cotista ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN estagio ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN acompanhamento_saude ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN projeto_pesquisa ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN projeto_extensao ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN projeto_ensino ENUM('Sim', 'Não') NOT NULL,
ADD COLUMN foto VARCHAR(255) DEFAULT NULL;


ALTER TABLE usuarios 
ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL;

ALTER TABLE setores 
ADD COLUMN foto_perfil VARCHAR(255) DEFAULT NULL;



CREATE TABLE `discentes_turmas` (
  `numero_matricula` INT NOT NULL,
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,
  `turma_ano_ingresso` YEAR NOT NULL,
  FOREIGN KEY (`numero_matricula`) REFERENCES `discentes` (`numero_matricula`) ON DELETE CASCADE,
  FOREIGN KEY (`turma_numero`) REFERENCES `turmas` (`numero`),
  PRIMARY KEY (`numero_matricula`, `turma_numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


ALTER TABLE docentes_disciplinas ADD COLUMN turma_numero VARCHAR(50), ADD COLUMN turma_ano INT;

ALTER TABLE turmas
ADD COLUMN presidente_id INT NUll,
ADD FOREIGN KEY (presidente_id) REFERENCES discentes(numero_matricula);

CREATE TABLE `notas` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `discente_id` INT NOT NULL,
  `disciplina_id` INT NOT NULL,
  `turma_numero` INT NOT NULL,
  `turma_ano` YEAR NOT NULL,

  -- Notas Parciais e Semestrais
  `parcial_1` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`parcial_1` >= 0 AND `parcial_1` <= 10),
  `ais` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`ais` >= 0 AND `ais` <= 10),
  `nota_semestre_1` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`nota_semestre_1` >= 0 AND `nota_semestre_1` <= 10),
  `parcial_2` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`parcial_2` >= 0 AND `parcial_2` <= 10),
  `mostra_ciencias` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`mostra_ciencias` >= 0 AND `mostra_ciencias` <= 10),
  `ppi` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`ppi` >= 0 AND `ppi` <= 10),
  `nota_semestre_2` DECIMAL(5, 2) NOT NULL DEFAULT 0 CHECK (`nota_semestre_2` >= 0 AND `nota_semestre_2` <= 10),

  -- Nota Final e Exame
  `nota_final` DECIMAL(5, 2) DEFAULT NULL CHECK (`nota_final` >= 0 AND `nota_final` <= 10),
  `nota_exame` DECIMAL(5, 2) DEFAULT NULL CHECK (`nota_exame` >= 0 AND `nota_exame` <= 10),

  -- Frequência
  `faltas` INT NOT NULL DEFAULT 0 CHECK (`faltas` >= 0),

  -- Informações adicionais
  `observacoes` TEXT DEFAULT NULL,

  PRIMARY KEY (`id`),

  -- Relações e integridade referencial
  FOREIGN KEY (`discente_id`) REFERENCES `discentes` (`numero_matricula`) ON DELETE CASCADE,
  FOREIGN KEY (`disciplina_id`) REFERENCES `disciplinas` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`turma_numero`) REFERENCES `turmas` (`numero`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_numero INT,
    discente_id INT,
    FOREIGN KEY (turma_numero) REFERENCES turmas(numero),
    FOREIGN KEY (discente_id) REFERENCES discentes(numero_matricula)
);


ALTER TABLE discentes_turmas
DROP FOREIGN KEY discentes_turmas_ibfk_2;

ALTER TABLE discentes_turmas
ADD CONSTRAINT discentes_turmas_ibfk_2
FOREIGN KEY (turma_numero) REFERENCES turmas(numero)
ON DELETE CASCADE
ON UPDATE CASCADE;

ALTER TABLE notas 
DROP FOREIGN KEY notas_ibfk_3;

ALTER TABLE notas 
ADD CONSTRAINT notas_ibfk_3 
FOREIGN KEY (turma_numero) 
REFERENCES turmas(numero) 
ON DELETE CASCADE 
ON UPDATE CASCADE;

ALTER TABLE turmas DROP FOREIGN KEY turmas_ibfk_1;

ALTER TABLE turmas ADD CONSTRAINT turmas_ibfk_1 
FOREIGN KEY (professor_regente) REFERENCES docentes(id) ON DELETE SET NULL;

-- 1. Inserir dados na tabela de usuários
INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `reset_token`, `reset_expires`, `tipo`)
VALUES
(0, 'César', 'cesar@gmail.com', '$2y$10$g5R/9WB7fF2MsKxqxE4Dt.5yYgx34uy.jeGlmjxGcxcOzWykABQX.', NULL, NULL, 'docente'),
(0, 'João', 'joao@gmail.com', '$2y$10$g5R/9WB7fF2MsKxqxE4Dt.5yYgx34uy.jeGlmjxGcxcOzWykABQX.', NULL, NULL, 'docente'),
(0, 'Daniel', 'daniel@gmail.com', '$2y$10$g5R/9WB7fF2MsKxqxE4Dt.5yYgx34uy.jeGlmjxGcxcOzWykABQX.', NULL, NULL, 'setor'),

(0, 'teste', 'teste@gmail.com', '$2y$10$QnEOOy0.g050eTuhFx2McOGuYxPLGJ0p31W8YPOiIIUsqjpoJvwG.', NULL, NULL, 'administrador'),
(0, 'Vicente Boscardin', 'vicenteboscardin@gmail.com', '$2y$10$QnEOOy0.g050eTuhFx2McOGuYxPLGJ0p31W8YPOiIIUsqjpoJvwG.', NULL, NULL, 'administrador');

-- 4. Inserir dados na tabela de setores
INSERT INTO `setores` (`id`, `local`, `nome`, `senha`)
VALUES
(0, 'CAE', 'Daniel', '$2y$10$g5R/9WB7fF2MsKxqxE4Dt.5yYgx34uy.jeGlmjxGcxcOzWykABQX.');


-- 5. Inserir dados na tabela de docentes
INSERT INTO `docentes` (`id`, `nome`, `email`, `siape`, `senha`)
VALUES
(0, 'César', 'cesar@gmail.com', '12345678902', '$2y$10$g5R/9WB7fF2MsKxqxE4Dt.5yYgx34uy.jeGlmjxGcxcOzWykABQX.'),
(0, 'João', 'joao@gmail.com', '12345678901', '$2y$10$g5R/9WB7fF2MsKxqxE4Dt.5yYgx34uy.jeGlmjxGcxcOzWykABQX.');

-- 2. Inserir dados na tabela de cursos
INSERT INTO `cursos` (`id`, `nome`, `coordenador`)
VALUES 
(0, 'Técnico em Informática', 0),
(0, 'Técnico em Administração', 0),
(0, 'Técnico em Agropecuária', 0);

-- 3. Inserir dados na tabela de disciplinas
INSERT INTO `disciplinas` (`id`, `curso_id`, `nome`)
VALUES
(0, 1, 'Matemática'),
(0, 2, 'Língua Portuguesa'),
(0, 1, 'Artes'),
(0, 1, 'Educação Física'),
(0, 1, 'Física'),
(0, 1, 'Geografia'),
(0, 1, 'História'),
(0, 1, 'Biologia'),
(0, 1, 'Química'),
(0, 1, 'Filosofia'),
(0, 1, 'Hardware'),
(0, 1, 'Inglês'),
(0, 1, 'Programação I'),
(0, 3, 'Contabilidade');



-- 6. Inserir dados na tabela de docentes_disciplinas
INSERT INTO `docentes_disciplinas` (`docente_id`, `disciplina_id`)
VALUES
(1, 1),
(1, 2),
(2, 3), 
(2, 4),
(1, 5),
(2, 6), 
(1, 7),
(2, 8), 
(1, 9),
(2, 10),
(1, 11),
(2, 12), 
(1, 13),  
(2, 14);

-- 7. Inserir dados na tabela de turmas
INSERT INTO `turmas` (`numero`, `ano`, `ano_ingresso`, `ano_oferta`, `professor_regente`, `curso_id`)
VALUES
(14, 2024, 2024, 2026, 1, 1), 
(24, 2024, 2023, 2025, 1, 1),
(34, 2024, 2022, 2024, 1, 1);

-- 8. Inserir dados na tabela de turmas_disciplinas
INSERT INTO `turmas_disciplinas` (`turma_numero`, `turma_ano`, `turma_ano_ingresso`, `disciplina_id`)
VALUES
(14, 2024, 2024, 1),
(14, 2024, 2024, 2),
(14, 2024, 2024, 3),
(14, 2024, 2024, 4),
(14, 2024, 2024, 5),
(14, 2024, 2024, 6),
(14, 2024, 2024, 7),
(14, 2024, 2024, 8),
(14, 2024, 2024, 9),
(14, 2024, 2024, 10),
(14, 2024, 2024, 11),
(14, 2024, 2024, 12),
(14, 2024, 2024, 13),
(14, 2024, 2024, 14);


-- 9. Inserir dados na tabela de discentes
INSERT INTO `discentes` (`numero_matricula`, `nome`, `cidade`, `email`, `genero`, `data_nascimento`, `observacoes`)
VALUES
(1, 'Carlos Silva', 'São Paulo', 'carlos@gmail.com', 'Masculino', '2000-03-10', 'Nenhuma'),
(2, 'Ana Souza', 'Rio de Janeiro', 'ana@gmail.com', 'Feminino', '2001-05-22', 'Necessita de acompanhamento psicológico'),
(3, 'João Pereira', 'Belo Horizonte', 'joao@gmail.com', 'Masculino', '2000-07-15', 'Apto para atividades extracurriculares'),
(4, 'Maria Oliveira', 'Curitiba', 'maria@gmail.com', 'Feminino', '1999-11-30', 'Sem observações'),
(5, 'Pedro Santos', 'Salvador', 'pedro@gmail.com', 'Masculino', '2002-01-20', 'Necessita de material adaptado'),
(6, 'Juliana Costa', 'Fortaleza', 'juliana@gmail.com', 'Feminino', '2003-06-14', 'Participa de atividades culturais'),
(7, 'Lucas Almeida', 'Porto Alegre', 'lucas@gmail.com', 'Masculino', '2000-09-25', 'Nenhuma'),
(8, 'Beatriz Martins', 'Recife', 'beatriz@gmail.com', 'Feminino', '2001-04-18', 'Faz parte do grupo de liderança'),
(9, 'Felipe Rodrigues', 'Manaus', 'felipe@gmail.com', 'Masculino', '1998-12-05', 'Apto para estágio'),
(10, 'Larissa Pereira', 'Goiânia', 'larissa@gmail.com', 'Feminino', '2002-08-11', 'Observação: Alergia a alimentos'),
(11, 'André Silva', 'São Luís', 'andre@gmail.com', 'Masculino', '2003-03-01', 'Participa de eventos esportivos'),
(12, 'Mariana Souza', 'Belém', 'mariana@gmail.com', 'Feminino', '2000-10-27', 'Nenhuma'),
(13, 'Roberto Costa', 'Natal', 'roberto@gmail.com', 'Masculino', '2001-09-12', 'Apto para viagens acadêmicas'),
(14, 'Gabriela Lima', 'Maceió', 'gabriela@gmail.com', 'Feminino', '2003-04-20', 'Necessita de acompanhamento de saúde mental'),
(15, 'Rafael Oliveira', 'Cuiabá', 'rafael@gmail.com', 'Masculino', '2002-11-05', 'Gostaria de participar de intercâmbio'),
(16, 'Carla Santos', 'São Bernardo do Campo', 'carla@gmail.com', 'Feminino', '2000-12-15', 'Sem observações'),
(17, 'Vitor Almeida', 'Florianópolis', 'vitor@gmail.com', 'Masculino', '2001-07-30', 'Tem interesse em iniciação científica'),
(18, 'Patrícia Rodrigues', 'Teresina', 'patricia@gmail.com', 'Feminino', '2000-05-18', 'Nenhuma'),
(19, 'Eduardo Lima', 'João Pessoa', 'eduardo@gmail.com', 'Masculino', '2002-02-08', 'Necessita de transporte acessível'),
(20, 'Caroline Costa', 'Aracaju', 'caroline@gmail.com', 'Feminino', '2003-09-13', 'Participa de eventos voluntários');


-- 10. Inserir dados na tabela de discentes_turmas
INSERT INTO `discentes_turmas` (`numero_matricula`, `turma_numero`, `turma_ano`, `turma_ano_ingresso`)
VALUES
(1, 14, 2024, 2024),
(2, 14, 2024, 2024),
(3, 14, 2024, 2024),
(4, 14, 2024, 2024),
(5, 14, 2024, 2024),
(6, 14, 2024, 2024),
(7, 14, 2024, 2024),
(8, 14, 2024, 2024),
(9, 14, 2024, 2024),
(10, 14, 2024, 2024),
(11, 14, 2024, 2024),
(12, 14, 2024, 2024),
(13, 14, 2024, 2024),
(14, 14, 2024, 2024),
(15, 14, 2024, 2024),
(16, 14, 2024, 2024),
(17, 14, 2024, 2024),
(18, 14, 2024, 2024),
(19, 14, 2024, 2024),
(20, 14, 2024, 2024);


-- 11. Inserir dados na tabela de notas
INSERT INTO `notas` (`discente_id`, `disciplina_id`, `turma_numero`, `turma_ano`, `parcial_1`, `nota_semestre_1`, `parcial_2`, `nota_semestre_2`, `nota_final`, `faltas`)
VALUES
(1, 1, 14, 2024, 8.5, 7.0, 9.0, 8.5, 7.5, 0),
(2, 1, 14, 2024, 7.5, 6.5, 8.5, 7.0, 7.0, 2),
(3, 1, 14, 2024, 9.0, 8.0, 9.5, 8.5, 9.0, 1),
(4, 1, 14, 2024, 7.0, 6.5, 8.0, 7.5, 6.5, 3),
(5, 1, 14, 2024, 8.0, 7.0, 9.0, 8.0, 7.5, 1),
(6, 1, 14, 2024, 9.0, 8.5, 9.5, 9.0, 9.0, 0),
(7, 1, 14, 2024, 6.5, 6.0, 8.0, 7.0, 6.5, 4),
(8, 1, 14, 2024, 8.0, 7.0, 8.5, 8.0, 7.5, 2),
(9, 1, 14, 2024, 7.5, 7.0, 8.5, 8.0, 7.5, 3),
(10, 1, 14, 2024, 8.5, 8.0, 9.0, 8.5, 8.0, 1),
(11, 1, 14, 2024, 9.0, 8.5, 9.5, 9.0, 9.5, 0),
(12, 1, 14, 2024, 7.0, 6.5, 8.0, 7.5, 7.0, 5),
(13, 1, 14, 2024, 8.0, 7.5, 8.5, 8.0, 7.5, 2),
(14, 1, 14, 2024, 8.5, 8.0, 9.0, 8.5, 8.5, 1),
(15, 1, 14, 2024, 7.5, 7.0, 8.5, 8.0, 7.5, 4),
(16, 1, 14, 2024, 8.0, 7.5, 9.0, 8.5, 8.0, 0),
(17, 1, 14, 2024, 9.5, 8.5, 9.5, 9.0, 9.0, 2),
(18, 1, 14, 2024, 7.5, 7.0, 8.0, 7.5, 7.0, 3),
(19, 1, 14, 2024, 8.5, 8.0, 9.0, 8.5, 8.5, 0),
(20, 1, 14, 2024, 8.0, 7.5, 8.5, 8.0, 7.5, 4);



-- 12. Inserir dados na tabela de matrículas
INSERT INTO `matriculas` (`turma_numero`, `discente_id`)
VALUES
(14, 1), 
(24, 2);

