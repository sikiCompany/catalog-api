# 🚀 CI/CD - Integração e Deploy Contínuo

Documentação completa do pipeline CI/CD implementado com GitHub Actions.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Workflows](#workflows)
- [Configuração](#configuração)
- [Secrets](#secrets)
- [Deploy](#deploy)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

O projeto utiliza **GitHub Actions** para automação de:
- ✅ Testes automatizados
- ✅ Análise de código (Lint)
- ✅ Verificação de segurança
- ✅ Cobertura de código
- ✅ Deploy automático (futuro)

### Triggers

Os workflows são executados em:
- **Push** para branches `main` e `develop`
- **Pull Requests** para `main` e `develop`

---

## 📦 Workflows

### 1. Tests Workflow

**Arquivo**: `.github/workflows/tests.yml`

Executa a suite completa de testes com serviços necessários.

#### Serviços

- **MySQL 8.0** - Banco de dados
- **Redis 7** - Cache
- **Elasticsearch 8.11** - Busca

#### Steps

1. **Checkout code** - Clona o repositório
2. **Setup PHP 8.2** - Configura PHP com extensões
3. **Install Dependencies** - Instala pacotes Composer
4. **Generate key** - Gera chave da aplicação
5. **Run Migrations** - Executa migrations
6. **Execute tests** - Roda testes com cobertura
7. **Upload coverage** - Envia cobertura para Codecov

#### Comando

```yaml
php artisan test --coverage --min=80
```

#### Requisitos

- Cobertura mínima: 80%
- Todos os testes devem passar

### 2. Code Quality Workflow

**Arquivo**: `.github/workflows/lint.yml`

Verifica qualidade e segurança do código.

#### Steps

1. **Checkout code** - Clona o repositório
2. **Setup PHP 8.2** - Configura PHP
3. **Install Dependencies** - Instala pacotes
4. **Run Laravel Pint** - Verifica formatação
5. **Security audit** - Verifica vulnerabilidades

#### Ferramentas

- **Laravel Pint** - Code style (PSR-12)
- **Composer Audit** - Vulnerabilidades de segurança

---

## ⚙️ Configuração

### Estrutura de Pastas

```
.github/
└── workflows/
    ├── tests.yml          # Testes automatizados
    └── lint.yml           # Qualidade de código
```

### Ambiente de Testes

Os workflows usam:
- **Ubuntu Latest** - Sistema operacional
- **PHP 8.2** - Versão do PHP
- **SQLite** - Banco para testes
- **Array Cache** - Cache em memória
- **Sync Queue** - Fila síncrona

### Extensões PHP

```yaml
extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, 
           sqlite, pdo_sqlite, pdo_mysql, bcmath, soap, 
           intl, gd, exif, iconv
```

---

## 🔐 Secrets

### Secrets Necessários

Configure no GitHub: `Settings > Secrets and variables > Actions`

#### 1. CODECOV_TOKEN (Opcional)

Token para upload de cobertura de código.

**Como obter:**
1. Acesse [codecov.io](https://codecov.io)
2. Conecte seu repositório
3. Copie o token
4. Adicione como secret no GitHub

#### 2. AWS Credentials (Para Deploy)

```
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_DEFAULT_REGION
AWS_BUCKET
```

**Como obter:**
1. Acesse AWS IAM Console
2. Crie um usuário com permissões S3
3. Gere Access Key
4. Adicione como secrets no GitHub

---

## 🚀 Deploy

### Deploy Manual

Para fazer deploy manual:

```bash
# 1. Build da aplicação
composer install --optimize-autoloader --no-dev

# 2. Otimizações
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Migrations
php artisan migrate --force

# 4. Restart services
php artisan queue:restart
```

### Deploy Automático (Futuro)

Workflow de deploy será adicionado para:
- Deploy em staging (branch develop)
- Deploy em produção (branch main)
- Rollback automático em caso de falha

---

## 📊 Status Badges

Adicione badges ao README.md:

### Tests

```markdown
![Tests](https://github.com/seu-usuario/catalog-api/workflows/Tests/badge.svg)
```

### Code Quality

```markdown
![Code Quality](https://github.com/seu-usuario/catalog-api/workflows/Code%20Quality/badge.svg)
```

### Coverage

```markdown
[![codecov](https://codecov.io/gh/seu-usuario/catalog-api/branch/main/graph/badge.svg)](https://codecov.io/gh/seu-usuario/catalog-api)
```

---

## 🐛 Troubleshooting

### Testes Falhando

**Problema**: Testes falham no CI mas passam localmente

**Soluções**:
1. Verifique versão do PHP
2. Verifique extensões instaladas
3. Limpe cache: `php artisan config:clear`
4. Verifique variáveis de ambiente

### Serviços Não Conectam

**Problema**: MySQL/Redis/Elasticsearch não conectam

**Soluções**:
1. Verifique health checks nos services
2. Aumente timeout de health check
3. Verifique portas configuradas
4. Verifique logs do workflow

### Lint Falhando

**Problema**: Laravel Pint encontra problemas

**Soluções**:
1. Execute localmente: `./vendor/bin/pint`
2. Corrija automaticamente: `./vendor/bin/pint`
3. Commit as correções

### Cobertura Baixa

**Problema**: Cobertura abaixo de 80%

**Soluções**:
1. Adicione mais testes
2. Remova código não testado
3. Ajuste threshold se necessário

---

## 📝 Boas Práticas

### 1. Commits

- Commits pequenos e focados
- Mensagens descritivas
- Seguir Conventional Commits

### 2. Pull Requests

- Criar PR para cada feature
- Aguardar CI passar antes de merge
- Solicitar code review

### 3. Branches

- `main` - Produção (protegida)
- `develop` - Desenvolvimento (protegida)
- `feature/*` - Features
- `fix/*` - Correções
- `hotfix/*` - Correções urgentes

### 4. Testes

- Escrever testes antes de PR
- Manter cobertura > 80%
- Testar localmente antes de push

---

## 🔄 Workflow Completo

```
1. Developer cria branch feature/nova-funcionalidade
   ↓
2. Developer faz commits
   ↓
3. Developer push para GitHub
   ↓
4. GitHub Actions executa:
   - Tests Workflow
   - Code Quality Workflow
   ↓
5. Se passar:
   - ✅ PR pode ser mergeado
   - ✅ Deploy automático (futuro)
   ↓
6. Se falhar:
   - ❌ Developer corrige
   - ❌ Repete processo
```

---

## 📚 Recursos

- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [Laravel Pint](https://laravel.com/docs/pint)
- [Codecov](https://docs.codecov.com)
- [PHPUnit](https://phpunit.de/documentation.html)

---

## 🎯 Próximos Passos

- [ ] Adicionar workflow de deploy
- [ ] Configurar ambientes (staging/production)
- [ ] Adicionar notificações (Slack/Discord)
- [ ] Implementar rollback automático
- [ ] Adicionar testes de performance
- [ ] Configurar cache de dependências
- [ ] Adicionar análise estática (PHPStan)

---

**Última atualização**: 2026-02-14  
**Versão**: 1.0.0  
**Status**: ✅ Implementado
