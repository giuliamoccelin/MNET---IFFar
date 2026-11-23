# Instalação e configuração do MNET

## Requisitos

- PHP 8.2 ou superior com `mysqli`, `mysqlnd`, `SimpleXML` e `proc_open` habilitados.
- MySQL 8 ou MariaDB compatível.
- Apache, Nginx ou servidor web equivalente.
- Nmap e o driver de captura apropriado. No Windows, instale também o Npcap oferecido pelo instalador do Nmap.

O computador que executa o Apache/PHP é quem realiza a varredura. Se o navegador estiver em outro computador, a rede detectada automaticamente será a rede do servidor, não a rede do navegador.

## Instalação em Windows com WAMP

1. Instale WAMP com PHP 8.2 ou superior.
2. Instale Nmap e Npcap. Os caminhos normalmente encontrados são:
   - `C:\Program Files (x86)\Nmap\nmap.exe`
   - `C:\Program Files\Nmap\nmap.exe`
3. Copie a pasta do projeto para o diretório web do WAMP.
4. Copie `config.local.example.php` para `config.local.php`.
5. Ajuste banco, usuário, senha, redes autorizadas e, se necessário, o caminho do Nmap.
6. No `php.ini`, confirme que `mysqli` e `SimpleXML` estão habilitados e que `proc_open` não está em `disable_functions`.
7. Reinicie o Apache depois de alterar o `php.ini`.

Não execute o Apache como administrador por padrão. Primeiro teste o perfil rápido. Se a detecção de sistema operacional não funcionar, verifique a instalação/serviço do Npcap e conceda apenas as permissões necessárias à conta do serviço.

## Banco de dados

Para testar com os dois usuários de demonstração, crie o banco `tccinventario_ti` no MySQL 8 e importe `database/tccinventario_ti (1).sql`. Esse dump já contém `admin@iffar.edu.br` e `usuario.comum@iffar.edu.br`, ambos com senha de teste `123`. Troque as senhas antes de disponibilizar a aplicação fora de um ambiente isolado.

Para uma instalação nova sem dados, importe `database/schema_corrigido.sql`. Ele **não** cria usuários. Nesse caso, o primeiro administrador deve ser inserido por um procedimento controlado com senha gerada por `password_hash()` ou migrado de uma base existente. Não importe o esquema novo por cima do dump original.

Para uma instalação que já utiliza o dump original:

1. faça backup completo;
2. corrija valores inválidos e duplicados, principalmente e-mail, CPF e MAC;
3. execute `database/migracao_banco_existente.sql`;
4. confira se as chaves estrangeiras foram criadas.

O script de migração pode falhar corretamente se houver registros órfãos ou duplicados. Não remova as constraints para esconder esses problemas; corrija os dados antes.

## Configuração de rede

Em `config.local.php`:

- `minimum_prefix = 24` limita cada execução a 256 endereços;
- `timeout_seconds = 45` define o teto de opções na tela: 25, 30 e 45 segundos;
- `default_timeout_seconds = 30` seleciona inicialmente 30 segundos;
- `max_detailed_hosts = 32` impede detalhamento síncrono de redes grandes;
- `allowed_networks` define quais blocos podem ser examinados.

Exemplos de tamanho:

| CIDR | Endereços examinados | Uso sugerido |
|---|---:|---|
| `/32` | 1 | um equipamento específico |
| `/28` | 16 | laboratório pequeno |
| `/27` | 32 | setor ou sala |
| `/26` | 64 | laboratório maior |
| `/25` | 128 | segmento médio |
| `/24` | 256 | limite padrão da execução rápida |

No modo **Rede conectada ao servidor**, a tela usa o IPv4 visto pelo PHP e alinha a rede ao tamanho selecionado. Se o servidor responder apenas como `127.0.0.1`, contêiner, proxy ou NAT, use o modo CIDR manual.

## Perfis de varredura

- **Rápido:** descoberta com `-sn`; normalmente cabe em uma requisição curta e retorna IP, MAC e fabricante quando disponíveis.
- **Equilibrado:** examina portas mais comuns e tenta identificar o sistema operacional somente nos hosts novos.
- **Detalhado:** acrescenta identificação de versões de serviços; é significativamente mais lento.

MAC e fabricante geralmente só são visíveis no mesmo segmento de camada 2. Equipamentos atrás de roteadores podem aparecer apenas com IP/hostname.

## Limites de tempo

Não existe um número universal de hosts que sempre termine antes do timeout. O tempo depende de latência, firewall, quantidade de hosts ativos, perfil e portas examinadas.

Por padrão, a tela permite 25, 30 e 45 segundos, com 30 selecionados. O PHP tenta ajustar `set_time_limit()` para dez segundos acima da escolha. Confirme que a função não está desabilitada e que Apache, proxy e navegador suportam essa duração. Para 45 segundos, configure o limite efetivo do PHP/servidor em pelo menos 55 a 60 segundos e meça o tempo real na rede autorizada.

Para oferecer 60 segundos, altere conscientemente `timeout_seconds` para `60` no `config.local.php`, ajuste o limite do PHP/servidor para pelo menos 70 a 75 segundos e teste a carga. Há um bloqueio que impede duas varreduras simultâneas nesta instalação, mas uma varredura longa continua ocupando um processo do servidor. Não aumente o teto para redes grandes sem medir CPU, memória e tempo de resposta das outras páginas.

Quando há mais hosts do que o limite de detalhamento, a página conserva a descoberta rápida e informa que não examinou portas/serviços, em vez de perder todos os resultados. Se o detalhamento falhar, a descoberta também permanece visível.

Use `/24` apenas no perfil rápido. Para perfil equilibrado, prefira até 32 hosts novos. Para perfil detalhado, comece com `/28` ou com um único `/32`.

Para redes maiores ou inventário detalhado de muitos hosts, transforme o scan em tarefa assíncrona: a requisição cria um trabalho, um processo de fila executa o Nmap e a tela consulta o progresso. Aumentar apenas `max_execution_time` prende workers do servidor e não resolve escalabilidade.

## Equipamentos já cadastrados

A tela carrega os equipamentos existentes e compara primeiro o MAC normalizado e depois o IPv4. Com a opção padrão, cadastrados não são exibidos e não passam pela segunda fase detalhada, economizando tempo.

Eles ainda participam da descoberta rápida. Excluir diretamente os IPs do comando Nmap seria mais rápido, mas poderia ocultar troca de dispositivo, DHCP ou alteração de MAC. Para redes com DHCP, mantenha o MAC como identificador principal e registre histórico de IPs em uma evolução futura.

## Diagnóstico

Se o botão não funcionar, confira nesta ordem:

1. o usuário conectado é administrador;
2. o CIDR pertence a `allowed_networks`;
3. `nmap.exe` existe no caminho configurado;
4. `proc_open` e SimpleXML estão disponíveis;
5. o serviço web consegue iniciar o Nmap;
6. o perfil e a faixa cabem no tempo configurado;
7. o log de erros do PHP/Apache contém detalhes técnicos.

## Atualização e recuperação

Os arquivos PHP não ficam mais todos na raiz do projeto: eles foram organizados nas pastas `admin/`, `usuario/`, `comum/` e `includes/` (veja o `README.md`). Ao atualizar uma instalação antiga, substitua a pasta inteira do projeto e mantenha apenas o seu `config.local.php`, que continua na raiz, ao lado do `config.php`. O endereço de entrada, `index.php`, não mudou, mas endereços salvos nos favoritos mudam de lugar (por exemplo, `admin.php` passa a ser `admin/admin.php`). Para verificar a sintaxe de todos os arquivos PHP no PowerShell, use `Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }`.

Para atualizar uma instalação existente, exporte o banco e faça uma cópia da pasta web antes de substituir os arquivos PHP/CSS. Mantenha o `config.local.php` local; compare-o com o novo `config.local.example.php` e ajuste apenas as novas chaves. A atualização de código desta versão não exige zerar nem migrar o banco.

Se desejar testar do zero no mesmo MySQL, crie **outro banco de teste**, por exemplo `tccinventario_ti_teste`, selecione-o no phpMyAdmin, importe nele o dump original e ajuste `db.name` na cópia do projeto. Preserve o banco atual como backup. O dump original não traz comando `USE` e contém os usuários de demonstração e dados de exemplo. Já o esquema corrigido cria tabelas vazias, sem usuários, e contém `CREATE DATABASE`/`USE tccinventario_ti`; para usá-lo num banco de teste com outro nome, altere essas duas linhas antes de importar. Não apague o banco atual antes de verificar o backup e a instalação nova.
