USE tccinventario_ti;

-- Faça backup antes de executar. Remova/corrija duplicidades de e-mail, CPF
-- e MAC antes de criar os índices únicos.

ALTER TABLE equipamento ENGINE=InnoDB;
ALTER TABLE manutencao ENGINE=InnoDB;
ALTER TABLE usuario ENGINE=InnoDB;

ALTER TABLE usuario
  MODIFY email VARCHAR(254) NOT NULL,
  MODIFY senha VARCHAR(255) NOT NULL,
  MODIFY nome VARCHAR(150) NOT NULL,
  MODIFY cpf CHAR(14) NOT NULL,
  MODIFY cargo ENUM('A','U') NOT NULL DEFAULT 'U',
  ADD UNIQUE KEY uq_usuario_email (email),
  ADD UNIQUE KEY uq_usuario_cpf (cpf);

ALTER TABLE equipamento
  MODIFY ipv4 VARCHAR(45) NOT NULL,
  MODIFY mac VARCHAR(17) DEFAULT NULL,
  MODIFY atividade ENUM('Em uso','Disponível','Descarte','Manutenção') NOT NULL DEFAULT 'Em uso',
  MODIFY sistema_operacional VARCHAR(255) DEFAULT NULL,
  ADD UNIQUE KEY uq_equipamento_mac (mac),
  ADD KEY idx_equipamento_ipv4 (ipv4),
  ADD KEY idx_equipamento_hostname (hostname),
  ADD KEY idx_equipamento_patrimonio (patrimonio);

ALTER TABLE manutencao
  MODIFY id_equipamento INT NOT NULL,
  MODIFY id_usuario INT NOT NULL,
  MODIFY tipo_manutencao ENUM('Preventiva','Software','Hardware','Rede') NOT NULL,
  MODIFY custo DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  MODIFY descricao TEXT NOT NULL,
  ADD KEY idx_manutencao_data (data_registro),
  ADD CONSTRAINT fk_manutencao_equipamento FOREIGN KEY (id_equipamento)
    REFERENCES equipamento (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT fk_manutencao_usuario FOREIGN KEY (id_usuario)
    REFERENCES usuario (id) ON UPDATE CASCADE ON DELETE RESTRICT,
  ADD CONSTRAINT chk_manutencao_custo CHECK (custo >= 0);
