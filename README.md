# Sistema de mapeameto de rede com inventário MNET - IFFar

O Trabalho consiste no desenvolvimento de um Sistema de Inventário de Equipamentos de TI, projetado especificamente para o contexto do Instituto Federal Farroupilha (IFFar) – Campus Uruguaiana.
O projeto busca transformar a gestão de ativos tecnológicos de uma tarefa manual e propensa a erros em um processo dinâmico e automatizado.
Abaixo, os principais pontos abordados pelo trabalho:

1. Problemática e Motivação

O trabalho parte da analogia do "quarto desarrumado", onde a falta de visibilidade sobre o que se possui gera desperdício de tempo, energia e recursos financeiros. No cenário do IFFar, a heterogeneidade da rede (computadores, servidores, pontos de acesso e dispositivos móveis) torna o controle manual insuficiente, deixando a infraestrutura vulnerável e sujeita a compras desnecessárias de itens já existentes em estoque.

2. Objetivo e Proposta

O objetivo principal é desenvolver um sistema com interface web e banco de dados que automatize a visibilidade sobre os recursos de TI. O diferencial do sistema é a sua abordagem mista:

Cadastro Manual: Para registrar dados que a rede não detecta, como o número do patrimônio público.

Descoberta Automática: Utilização da ferramenta Nmap para escanear a rede e identificar novos dispositivos em tempo real, garantindo uma base de dados sempre atualizada.

3. Tecnologias Utilizadas

O projeto preza pelo baixo custo e eficiência, utilizando exclusivamente ferramentas de software livre (Open Source), o que é ideal para uma instituição pública. As tecnologias incluem:

Linguagens: PHP (Backend), HTML, CSS e Bootstrap (Frontend).
Banco de Dados: MySQL (armazenamento centralizado).
Servidor e Ferramentas: WAMP para servidor local e Nmap para a varredura lógica da rede.

4. Fundamentação Teórica e IoT

O TCC é fundamentado em pesquisas recentes, como a dissertação de Welber Santos de Oliveira (2025), que trata da arquitetura de inventário para Sistemas IoT. Dessa fonte, o trabalho extrai a importância da resiliência cibernética e da visibilidade de dispositivos "invisíveis" (sensores, câmeras e smart TVs), utilizando protocolos como mDNS e SSDP para além do tradicional ping (ICMP).

5. Funcionalidades do Sistema

Dashboard Administrativo: Exibição de equipamentos por situação, manutenções, custo mensal, qualidade dos dados e total de usuários.
Gestão de Manutenção: Registro de alterações e reparos realizados nos equipamentos.
Segurança e Governança: O sistema distingue perfis e restringe ações administrativas. A classificação de ativos por criticidade ainda é uma evolução proposta, não uma funcionalidade implementada.

6. Estrutura de Pastas

O código foi organizado em pastas de acordo com a função de cada arquivo. A página de login e os arquivos de configuração ficam na raiz do projeto, e o acesso ao sistema continua sendo feito por `index.php`.

```
tcc_inventario/
├── index.php                  Página de login
├── config.php                 Configuração padrão (banco, Nmap e redes permitidas)
├── config.local.example.php   Modelo da configuração de cada computador
├── admin/                     Páginas do administrador: painel, listagens, cadastros, perfil e escaneamento
├── usuario/                   Páginas do usuário comum
├── comum/                     Páginas usadas pelos dois perfis: logout e relatório de manutenção
├── includes/                  Arquivos reutilizados: conexão, segurança, cabeçalho, menus e painel
├── assets/css/                Folha de estilo
├── assets/img/                Imagens e ícones
├── database/                  Scripts e dump do banco de dados
└── docs/                      Documentação
```

Os arquivos de `includes/` são usados por páginas de pastas diferentes. Por isso, os links dos menus e as imagens partem da constante `BASE_URL`, definida em `includes/caminhos.php`, que descobre sozinha o endereço do projeto no servidor. Dessa forma, o sistema funciona em qualquer pasta do servidor web sem configuração adicional.

As instruções atuais de instalação, configuração de rede e testes estão em `docs/INSTALACAO_E_CONFIGURACAO.md`. Antes de publicar dados ou código, remova informações pessoais do dump de demonstração e altere as senhas de teste.

Em suma, o trabalho não busca apenas criar uma lista de equipamentos, mas estabelecer um instrumento estratégico de gestão e segurança, otimizando o tempo da equipe técnica e reduzindo desperdícios institucionais.
