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

INSERT INTO `usuarios` (`id`, `username`, `email`, `password_hash`, `reset_token`, `reset_expires`, `tipo`)
VALUES
(0, 'teste', 'teste@gmail.com', '$2y$10$QnEOOy0.g050eTuhFx2McOGuYxPLGJ0p31W8YPOiIIUsqjpoJvwG.', NULL, NULL, 'administrador');

CREATE TABLE `cursos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(255) NOT NULL,
    `coordenador` VARCHAR(100) NOT NULL
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
  `cpf` varchar(14) NOT NULL,
  `senha` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `docentes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `cpf` varchar(14) NOT NULL UNIQUE,
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
  `professor_regente` INT NOT NULL,
  PRIMARY KEY (`numero`, `ano`, `ano_ingresso`),
  FOREIGN KEY (`professor_regente`) REFERENCES `docentes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
