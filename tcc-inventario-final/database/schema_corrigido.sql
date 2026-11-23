CREATE DATABASE IF NOT EXISTS tccinventario_ti
  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE tccinventario_ti;

CREATE TABLE usuario (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  email VARCHAR(254) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  nome VARCHAR(150) NOT NULL,
  cpf CHAR(14) NOT NULL,
  cargo ENUM('A','U') NOT NULL DEFAULT 'U',
  data_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuario_email (email),
  UNIQUE KEY uq_usuario_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE equipamento (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  hostname VARCHAR(100) DEFAULT NULL,
  ipv4 VARCHAR(45) NOT NULL,
  mac VARCHAR(17) DEFAULT NULL,
  patrimonio VARCHAR(100) DEFAULT NULL,
  local VARCHAR(100) DEFAULT NULL,
  atividade ENUM('Em uso','Disponível','Descarte','Manutenção') NOT NULL DEFAULT 'Em uso',
  marca VARCHAR(100) DEFAULT NULL,
  tipo_equipamento VARCHAR(100) DEFAULT NULL,
  data_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sistema_operacional VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_equipamento_mac (mac),
  KEY idx_equipamento_ipv4 (ipv4),
  KEY idx_equipamento_hostname (hostname),
  KEY idx_equipamento_patrimonio (patrimonio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE manutencao (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_equipamento INT UNSIGNED NOT NULL,
  id_usuario INT UNSIGNED NOT NULL,
  tipo_manutencao ENUM('Preventiva','Software','Hardware','Rede') NOT NULL,
  custo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descricao TEXT NOT NULL,
  data_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_manutencao_equipamento (id_equipamento),
  KEY idx_manutencao_usuario (id_usuario),
  KEY idx_manutencao_data (data_registro),
  CONSTRAINT fk_manutencao_equipamento FOREIGN KEY (id_equipamento)
    REFERENCES equipamento (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_manutencao_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuario (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_manutencao_custo CHECK (custo >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
