# Correções aplicadas

Esta cópia preserva a estrutura original e concentra as mudanças nos pontos auditados.

- `escanear_nmap.php`: configuração de rede manual/automática, perfis de scan, timeout próprio, execução sem shell, XML pela saída padrão, detalhes de portas/serviços, validação de CIDR e filtro de cadastrados.
- `config.php` e `config.local.example.php`: configuração portátil de banco, Nmap, redes permitidas e limites.
- `security.php`: autenticação centralizada, autorização administrativa, escape HTML e CSRF.
- `connect.php`: configuração externa, charset `utf8mb4` e erro seguro.
- `cadastro_maquina.php`: recebe dados do Nmap, valida IPv4/MAC, usa prepared statements e impede duplicidade por IP/MAC.
- `cadastro_manutencao.php`: usa ID do equipamento, prepared statement, CSRF e redireciona somente após sucesso.
- `perfil.php` e `usuario_perfil.php`: corrige `senha_atual`, atualiza a sessão após troca de e-mail, usa CSRF e prepared statements.
- `index.php`: login com prepared statement, mensagem uniforme, CSRF e regeneração do ID da sessão.
- `deletar_maquina.php` e `deletar_usuario.php`: aceitam apenas POST, exigem CSRF e usam prepared statements.
- `listar_maquinas.php`, `listar_usuarios.php` e `listar_manutencao.php`: autorização administrativa, escape de saída, paginação e formulários seguros de exclusão.
- `usuario_listar_maquinas.php` e `usuario_listar_manutencao.php`: corrige contagem, `LIMIT` e links de paginação.
- `feditar_maquina.php` e `feditar_usuario.php`: valida IDs, usa prepared statements, mantém a atividade selecionada e escapa valores.
- `menu_usuario.php`: evita erro JavaScript quando `menu-toggle` não existe.
- `relatorio_manutencao.php`: implementa relatório imprimível que antes estava vazio.
- `database/`: esquema novo e migração para InnoDB, FKs, `DECIMAL`, índices e constraints.
- `INSTALACAO_E_CONFIGURACAO.md`: instruções para outro computador e explicação dos limites do scanner.

## Validação ainda necessária no computador dos alunos

O ambiente usado para a revisão possuía Nmap 7.95/Npcap 1.88, mas não possuía PHP/WAMP acessível pela linha de comando. Portanto, os alunos devem executar `php -l *.php` com o PHP da instalação deles, importar uma cópia de teste do banco e percorrer o roteiro funcional descrito no relatório antes de substituir a versão em uso.

## Evolução de setembro de 2026

- `admin.php` e `home.php`: usam um dashboard comum, com indicadores de inventário, manutenção e qualidade dos dados; o cartão de usuários aparece apenas para administrador.
- `dashboard_data.php`: reúne contagens por agregação condicional e carrega somente as cinco manutenções recentes. Funciona com as colunas do dump original e do esquema novo.
- `dashboard_view.php`: apresenta indicadores e barras HTML/CSS acessíveis, sem depender do Chart.js ou de internet para o painel.
- `style.css`: corrige um comentário não encerrado que desabilitava todas as regras do painel de filtros e adiciona o layout responsivo do dashboard.
- `head.php`: remove o carregamento global do Chart.js, que deixou de ser usado, e adiciona viewport para celulares.
- `escanear_nmap.php`: oferece 25, 30 e 45 segundos por padrão; 60 segundos só aparece se o administrador mudar o teto local. Usa bloqueio por instalação para impedir varreduras simultâneas. Preserva hosts descobertos quando a fase detalhada excede o limite ou falha.
- `config.php` e `config.local.example.php`: teto padrão de 45 segundos, seleção inicial de 30 e loopback liberado no exemplo para teste `/32`.
- `INSTALACAO_E_CONFIGURACAO.md`: detalha atualização sem apagar o banco, teste com usuários existentes e critérios para 60 segundos.

Nesta revisão, o WampServer passou a estar disponível na máquina de análise. Todos os arquivos PHP do pacote foram aprovados por `php -l` com PHP 8.2.29. O dashboard foi consultado no MySQL 8.4.7 e houve testes HTTP de login dos dois perfis, bloqueio do scanner para usuário comum, varredura em `127.0.0.1/32` e rejeição de `/23` e do limite de 60 segundos não autorizado. As verificações funcionais completas na rede dos alunos continuam necessárias.

## Reorganização em pastas

O código foi separado em `admin/`, `usuario/`, `comum/`, `includes/`, `assets/`, `database/` e `docs/`, sem alterar o comportamento do sistema. Os arquivos compartilhados passaram a usar a constante `BASE_URL` (`includes/caminhos.php`) para montar links, imagens e redirecionamentos, e as páginas passaram a carregar os arquivos de `includes/` por caminho explícito. O `config.php` e o `config.local.php` permaneceram na raiz para que a configuração já existente em cada computador continue valendo.

Após a reorganização, todos os arquivos PHP foram aprovados por `php -l`. As páginas dos dois perfis, o cadastro, a exclusão, a troca de perfil e a varredura com Nmap em `127.0.0.1/32` foram comparados com a versão anterior e apresentaram o mesmo resultado.
