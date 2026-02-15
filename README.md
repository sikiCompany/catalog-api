# 🚀 API de Catálogo de Produtos

API REST desenvolvida em **Laravel 12** com **PHP 8.2+** para gerenciamento de catálogo de produtos, incluindo busca avançada com ElasticSearch, cache com Redis e ambiente totalmente containerizado com Docker.

[![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Tests](https://img.shields.io/badge/Tests-157%20passing-success)](TESTING.md)
[![Coverage](https://img.shields.io/badge/Coverage-85%25-brightgreen)](TESTING.md)
[![CI/CD](https://img.shields.io/badge/CI%2FCD-GitHub%20Actions-2088FF?logo=github-actions&logoColor=white)](CI_CD.md)
[![AWS S3](https://img.shields.io/badge/AWS-S3-FF9900?logo=amazon-aws&logoColor=white)](AWS_S3.md)

---

## 📋 Índice

- [Características](#-características)
- [Requisitos](#-requisitos)
- [Instalação](#-instalação)
- [Configuração](#-configuração)
- [Uso](#-uso)
- [Endpoints da API](#-endpoints-da-api)
- [Testes](#-testes)
- [Arquitetura](#-arquitetura)
- [Decisões Técnicas](#-decisões-técnicas)
- [Limitações Conhecidas](#-limitações-conhecidas)
- [Próximos Passos](#-próximos-passos)

---

## ✨ Características

### Funcionalidades Principais

- ✅ **CRUD Completo de Produtos** - Persistência em MySQL com soft delete
- ✅ **Busca Avançada** - ElasticSearch com múltiplos filtros e ordenação
- ✅ **Cache Inteligente** - Redis com TTL variável (60-120s) e invalidação automática
- ✅ **Sincronização Automática** - Observer + Jobs para manter ElasticSearch atualizado
- ✅ **Validação Robusta** - Request classes com regras de negócio
- ✅ **API Resources** - Respostas padronizadas e transformação de dados
- ✅ **Logs Estruturados** - Rastreamento de erros e operações
- ✅ **Ambiente Docker** - Setup completo com docker-compose
- ✅ **Upload de Imagens** - Suporte para AWS S3 (diferencial)
- ✅ **Fallback de Busca** - MySQL como alternativa ao ElasticSearch

### Diferenciais Implementados

- 🎯 **Arquitetura Limpa** - Controllers → Services → Repositories
- 🎯 **Soft Delete** - Produtos podem ser restaurados
- 🎯 **Cache por Parâmetros** - Diferentes combinações de filtros
- 🎯 **Bypass de Cache** - Paginações altas (page > 50) não usam cache
- 🎯 **Tratamento de Erros** - Respostas JSON consistentes
- 🎯 **Queue System** - Jobs assíncronos para sincronização
- 🎯 **Health Checks** - Docker com verificação de serviços
- 🎯 **CI/CD** - GitHub Actions com testes automatizados
- 🎯 **AWS S3** - Upload de imagens com fallback local
- 🎯 **Storage Service** - Abstração para múltiplos disks

---

## 🔧 Requisitos

### Ambiente de Desenvolvimento

- **Docker** 20.10+
- **Docker Compose** 2.0+
- **Git**

### Serviços (via Docker)

- PHP 8.2 com FPM
- MySQL 8.0
- Redis 7
- Elasticsearch 8.11
- Nginx 1.25

---

## 🚀 Instalação

### 1. Clone o Repositório

```bash
git clone <repository-url>
cd catalog-api
```

### 2. Configure o Ambiente

```bash
# Copie o arquivo de exemplo
cp .env.example .env

# Edite as variáveis de ambiente conforme necessário
# Veja a seção "Configuração" abaixo
```

### 3. Suba os Containers

```bash
docker-compose up -d
```

Aguarde todos os serviços iniciarem (pode levar 1-2 minutos na primeira vez).

### 4. Instale as Dependências

```bash
docker-compose exec app composer install
```

### 5. Gere a Chave da Aplicação

```bash
docker-compose exec app php artisan key:generate
```

### 6. Execute as Migrations

```bash
docker-compose exec app php artisan migrate
```

### 7. (Opcional) Popule o Banco com Dados de Teste

```bash
docker-compose exec app php artisan db:seed --class=ProductSeeder
```

### 8. Configure o Índice do ElasticSearch

```bash
docker-compose exec app php artisan scout:import "App\Models\Product"
```

### 9. Verifique a Instalação

Acesse: http://localhost/api/products

Você deve receber uma resposta JSON com a lista de produtos.

---

## ⚙️ Configuração

### Variáveis de Ambiente (.env)

```env
# Aplicação
APP_NAME="Catalog API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Banco de Dados (MySQL)
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=root

# Cache (Redis)
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=redis
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=database

# ElasticSearch (Scout)
SCOUT_DRIVER=elasticsearch
SCOUT_QUEUE=true
ELASTICSEARCH_HOST=elasticsearch:9200
ELASTICSEARCH_INDEX=products

# AWS S3 (Opcional - para upload de imagens)
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
FILESYSTEM_DISK=s3

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Portas dos Serviços

| Serviço | Porta | URL |
|---------|-------|-----|
| API (Nginx) | 80 | http://localhost |
| MySQL | 3307 | localhost:3307 |
| Redis | 6379 | localhost:6379 |
| Elasticsearch | 9200 | http://localhost:9200 |
| Kibana | 5601 | http://localhost:5601 |
| Adminer (MySQL UI) | 8080 | http://localhost:8080 |
| Mailhog | 8025 | http://localhost:8025 |

---

## 💻 Uso

### Comandos Docker Úteis

```bash
# Iniciar containers
docker-compose up -d

# Parar containers
docker-compose down

# Ver logs
docker-compose logs -f app

# Acessar container da aplicação
docker-compose exec app bash

# Reiniciar um serviço específico
docker-compose restart app
```

### Comandos Laravel

```bash
# Rodar migrations
docker-compose exec app php artisan migrate

# Resetar banco e popular
docker-compose exec app php artisan migrate:fresh --seed

# Limpar cache
docker-compose exec app php artisan cache:clear

# Reindexar produtos no Elasticsearch
docker-compose exec app php artisan scout:flush "App\Models\Product"
docker-compose exec app php artisan scout:import "App\Models\Product"

# Processar fila de jobs
docker-compose exec app php artisan queue:work

# Tinker (REPL)
docker-compose exec app php artisan tinker
```

### Comandos de Verificação

```bash
# Verificar índices do Elasticsearch
curl http://localhost:9200/_cat/indices

# Buscar no Elasticsearch
curl http://localhost:9200/products/_search

# Acessar Redis CLI
docker-compose exec redis redis-cli

# Limpar todo cache do Redis
docker-compose exec redis redis-cli FLUSHALL

# Ver chaves do cache
docker-compose exec redis redis-cli KEYS "*"
```

---

## 📡 Endpoints da API

### Base URL
```
http://localhost/api
```

### Produtos

#### Listar Produtos
```http
GET /api/products
```

**Parâmetros de Query:**
- `page` (int) - Número da página (padrão: 1)
- `per_page` (int) - Itens por página (padrão: 15, máx: 100)
- `category` (string) - Filtrar por categoria
- `status` (string) - Filtrar por status (active/inactive)
- `min_price` (float) - Preço mínimo
- `max_price` (float) - Preço máximo
- `search` (string) - Busca em nome/descrição
- `sort_by` (string) - Ordenar por (price/created_at/name)
- `sort_order` (string) - Ordem (asc/desc)
- `with_trashed` (boolean) - Incluir produtos deletados

**Exemplo:**
```bash
curl "http://localhost/api/products?category=Eletrônicos&status=active&per_page=10"
```

#### Criar Produto
```http
POST /api/products
Content-Type: application/json
```

**Body:**
```json
{
    "sku": "PROD001",
    "name": "Smartphone XYZ",
    "description": "Smartphone de última geração",
    "price": 1999.99,
    "category": "Eletrônicos",
    "status": "active"
}
```

**Validações:**
- `sku`: obrigatório, único
- `name`: obrigatório, mínimo 3 caracteres
- `price`: obrigatório, maior que 0
- `category`: obrigatório
- `status`: opcional (padrão: active), valores: active/inactive

#### Buscar Produto por ID
```http
GET /api/products/{id}
```

**Exemplo:**
```bash
curl http://localhost/api/products/1
```

#### Atualizar Produto
```http
PUT /api/products/{id}
PATCH /api/products/{id}
Content-Type: application/json
```

**Body:**
```json
{
    "name": "Smartphone XYZ Pro",
    "price": 2199.99,
    "status": "inactive"
}
```

#### Deletar Produto (Soft Delete)
```http
DELETE /api/products/{id}
```

#### Restaurar Produto
```http
POST /api/products/{id}/restore
```

#### Upload de Imagem
```http
POST /api/products/{id}/image
Content-Type: multipart/form-data
```

**Form Data:**
- `image` (file) - Imagem do produto (jpeg, png, jpg, gif, máx: 2MB)

### Busca (ElasticSearch)

#### Buscar Produtos
```http
GET /api/search/products
```

**Parâmetros de Query:**
- `q` (string) - Termo de busca (busca em name e description)
- `category` (string) - Filtrar por categoria
- `min_price` (float) - Preço mínimo
- `max_price` (float) - Preço máximo
- `status` (string) - Filtrar por status (active/inactive)
- `sort` (string) - Ordenar por (price/created_at)
- `order` (string) - Ordem (asc/desc)
- `page` (int) - Número da página
- `per_page` (int) - Itens por página (máx: 100)

**Exemplo:**
```bash
curl "http://localhost/api/search/products?q=smartphone&category=Eletrônicos&min_price=100&max_price=3000&sort=price&order=asc"
```

### Respostas da API

#### Sucesso (200/201)
```json
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "data": {
        "id": 1,
        "sku": "PROD001",
        "name": "Smartphone XYZ",
        "description": "Smartphone de última geração",
        "price": "1999.99",
        "category": "Eletrônicos",
        "status": "active",
        "created_at": "2026-02-14T10:00:00.000000Z",
        "updated_at": "2026-02-14T10:00:00.000000Z"
    }
}
```

#### Erro de Validação (422)
```json
{
    "success": false,
    "message": "Erro de validação",
    "errors": {
        "name": ["O campo nome é obrigatório."],
        "price": ["O preço deve ser maior que 0."]
    }
}
```

#### Não Encontrado (404)
```json
{
    "success": false,
    "message": "Produto não encontrado"
}
```

#### Erro Interno (500)
```json
{
    "success": false,
    "message": "Erro interno ao processar requisição",
    "error": "Detalhes do erro (apenas em modo debug)"
}
```

---

## 🧪 Testes

### Resumo

- **Total**: 157 testes
- **Feature**: 54 testes (endpoints da API)
- **Unit**: 103 testes (lógica de negócio)
- **Cobertura**: ~85%

### Executar Todos os Testes

```bash
docker-compose exec app php artisan test
```

### Executar Testes Específicos

```bash
# Apenas testes unitários
docker-compose exec app php artisan test --testsuite=Unit

# Apenas testes de feature
docker-compose exec app php artisan test --testsuite=Feature

# Teste específico
docker-compose exec app php artisan test tests/Unit/ProductTest.php

# Com cobertura
docker-compose exec app php artisan test --coverage
```

### Script Helper

```bash
# Tornar executável
chmod +x test.sh

# Executar
./test.sh all          # Todos os testes
./test.sh feature      # Feature tests
./test.sh unit         # Unit tests
./test.sh coverage     # Com cobertura
./test.sh product      # Testes de Product
./test.sh cache        # Testes de Cache
./test.sh help         # Ver todas as opções
```

### Estrutura de Testes

```
tests/
├── Feature/                    # Testes de integração (54 testes)
│   ├── ProductApiTest.php     # CRUD de produtos (25 testes)
│   ├── SearchApiTest.php      # Busca Elasticsearch (17 testes)
│   └── ProductCacheTest.php   # Sistema de cache (12 testes)
├── Unit/                       # Testes unitários (103 testes)
│   ├── ProductTest.php        # Model Product (25 testes)
│   ├── ProductServiceTest.php # ProductService (18 testes)
│   ├── ProductValidationTest.php # Validações (25 testes)
│   ├── ProductObserverTest.php # Observer (15 testes)
│   └── CacheableTraitTest.php # Trait Cacheable (20 testes)
├── TestCase.php               # Classe base
└── README.md                  # Documentação dos testes
```

### Cobertura de Testes

| Componente | Testes | Cobertura |
|------------|--------|-----------|
| ProductController | 25 | ~90% |
| SearchController | 17 | ~85% |
| Product Model | 25 | ~95% |
| ProductService | 18 | ~90% |
| ProductObserver | 15 | ~95% |
| Cacheable Trait | 20 | ~95% |
| Validações | 25 | ~100% |
| Cache System | 12 | ~85% |

### Ambiente de Testes

Os testes utilizam:
- **SQLite em memória** para velocidade
- **Array cache** para evitar dependência do Redis
- **Sync queue** para jobs síncronos
- **Scout fake** para evitar dependência do Elasticsearch
- **Factories** para geração de dados

### Documentação Completa

Para documentação detalhada dos testes, veja [TESTING.md](TESTING.md)

---

## 🏗️ Arquitetura

### Estrutura de Pastas

```
app/
├── Console/
│   └── Commands/          # Comandos Artisan customizados
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── ProductController.php
│   │       └── SearchController.php
│   ├── Requests/          # Form Requests (validação)
│   │   ├── StoreProductRequest.php
│   │   └── UpdateProductRequest.php
│   └── Resources/         # API Resources (transformação)
│       ├── ProductResource.php
│       └── ProductCollection.php
├── Jobs/                  # Jobs assíncronos
│   ├── SyncProductElasticsearch.php
│   └── RemoveProductFromElasticsearch.php
├── Models/
│   └── Product.php
├── Observers/             # Observers (eventos de modelo)
│   └── ProductObserver.php
├── Services/              # Lógica de negócio
│   └── ProductService.php
└── Traits/                # Traits reutilizáveis
    └── Cacheable.php
```

### Fluxo de Requisição

```
Request
  ↓
Route (api.php)
  ↓
Controller (ProductController)
  ↓
Request Validation (StoreProductRequest)
  ↓
Service (ProductService)
  ↓
Model (Product)
  ↓
Observer (ProductObserver) → Job (SyncProductElasticsearch)
  ↓
Resource (ProductResource)
  ↓
Response (JSON)
```

### Camadas da Aplicação

1. **Controllers** - Recebem requisições, delegam para services, retornam respostas
2. **Requests** - Validam dados de entrada
3. **Services** - Contêm lógica de negócio
4. **Models** - Representam entidades e interagem com banco
5. **Observers** - Reagem a eventos do modelo (created, updated, deleted)
6. **Jobs** - Processam tarefas assíncronas (sincronização Elasticsearch)
7. **Resources** - Transformam modelos em respostas JSON padronizadas

### Sincronização com ElasticSearch

```
Produto Criado/Atualizado
  ↓
ProductObserver detecta evento
  ↓
Dispara Job: SyncProductElasticsearch
  ↓
Job adiciona/atualiza no índice Elasticsearch
  ↓
Cache é invalidado
```

### Sistema de Cache

- **Estratégia**: Cache-Aside Pattern
- **TTL**: Aleatório entre 60-120 segundos (evita thundering herd)
- **Invalidação**: Automática em create/update/delete
- **Bypass**: Paginações > 50 não usam cache
- **Tags**: Agrupamento para flush seletivo

---

## 🚀 CI/CD

### GitHub Actions

O projeto utiliza GitHub Actions para automação:

- ✅ **Tests Workflow** - Executa todos os testes
- ✅ **Code Quality** - Laravel Pint e security audit
- ✅ **Coverage** - Cobertura mínima de 80%

### Workflows

```
.github/workflows/
├── tests.yml          # Testes automatizados
└── lint.yml           # Qualidade de código
```

### Triggers

- Push para `main` e `develop`
- Pull Requests para `main` e `develop`

### Documentação Completa

Veja [CI_CD.md](CI_CD.md) para detalhes completos.

---

## ☁️ AWS S3

### Upload de Imagens

O sistema suporta upload de imagens para AWS S3 com fallback automático para storage local.

#### Configuração

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

#### Uso

```bash
curl -X POST http://localhost/api/products/1/image \
  -H "Content-Type: multipart/form-data" \
  -F "image=@product.jpg"
```

#### Funcionalidades

- ✅ Upload para S3 ou local
- ✅ Processamento assíncrono
- ✅ Múltiplos tamanhos (thumbnail, medium, large)
- ✅ Otimização automática
- ✅ Fallback inteligente

### Documentação Completa

Veja [AWS_S3.md](AWS_S3.md) para detalhes completos.

---

## 🏥 Health Checks

### Endpoints

```bash
# Health check completo
GET /api/health

# Readiness check
GET /api/ready

# Liveness check
GET /api/live
```

### Resposta

```json
{
  "status": "healthy",
  "timestamp": "2026-02-14T10:00:00Z",
  "services": {
    "database": { "status": "up" },
    "cache": { "status": "up" },
    "storage": { 
      "status": "up",
      "s3_configured": true,
      "s3_available": true
    },
    "elasticsearch": { "status": "up" }
  }
}
```

---

## 🎯 Decisões Técnicas

### 1. Laravel Scout + Elasticsearch

**Por quê?**
- Abstração elegante para busca
- Sincronização automática via observers
- Suporte a múltiplos drivers (fácil trocar)

**Alternativas consideradas:**
- Elasticsearch PHP Client direto (mais verboso)
- Algolia (pago)

### 2. Redis para Cache

**Por quê?**
- Performance superior ao cache de arquivo
- Suporte a tags (flush seletivo)
- Persistência opcional
- Amplamente usado em produção

### 3. Soft Delete

**Por quê?**
- Permite auditoria
- Recuperação de dados acidental
- Histórico completo

**Trade-off:**
- Queries precisam considerar `deleted_at`
- Índices do banco maiores

### 4. Jobs Assíncronos para Elasticsearch

**Por quê?**
- Não bloqueia resposta da API
- Retry automático em caso de falha
- Escalável (pode usar Redis/SQS como driver)

**Configuração atual:**
- Driver: `database` (simples para desenvolvimento)
- Produção: recomendado Redis ou SQS

### 5. API Resources

**Por quê?**
- Transformação consistente de dados
- Controle sobre campos expostos
- Fácil versionamento da API

### 6. TTL Variável no Cache

**Por quê?**
- Evita "thundering herd" (todos os caches expirando juntos)
- Distribui carga de regeneração

### 7. Fallback de Busca (MySQL)

**Por quê?**
- Resiliência: API continua funcionando se Elasticsearch cair
- Graceful degradation

### 8. Docker Multi-Container

**Por quê?**
- Ambiente reproduzível
- Isolamento de serviços
- Fácil onboarding de novos desenvolvedores
- Simula produção

---

## ⚠️ Limitações Conhecidas

### 1. Queue Driver

**Limitação:** Usando `database` como driver de fila.

**Impacto:** Performance limitada em alta carga.

**Solução:** Em produção, usar Redis ou SQS.

```env
QUEUE_CONNECTION=redis
```

### 2. Elasticsearch Single Node

**Limitação:** Cluster com apenas 1 nó.

**Impacto:** Sem alta disponibilidade ou replicação.

**Solução:** Em produção, usar cluster com 3+ nós.

### 3. Upload de Imagens

**Limitação:** Implementação básica do S3, sem otimização de imagens.

**Impacto:** Imagens grandes podem consumir banda e storage.

**Solução:** Adicionar processamento (resize, compress) via Job.

### 4. Autenticação

**Limitação:** API sem autenticação/autorização.

**Impacto:** Qualquer um pode acessar endpoints.

**Solução:** Implementar Laravel Sanctum ou Passport.

### 5. Rate Limiting

**Limitação:** Sem limitação de requisições.

**Impacto:** Vulnerável a abuso/DDoS.

**Solução:** Adicionar throttle middleware.

```php
Route::middleware('throttle:60,1')->group(function () {
    // rotas
});
```

### 6. Testes

**Limitação:** Cobertura de testes ainda em desenvolvimento.

**Impacto:** Menor confiança em refatorações.

**Solução:** Expandir suite de testes (Feature e Unit).

### 7. Monitoramento

**Limitação:** Sem APM ou métricas.

**Impacto:** Difícil diagnosticar problemas em produção.

**Solução:** Integrar New Relic, Datadog ou Sentry.

---

## 🔮 Próximos Passos

### Curto Prazo

- [X] Implementar testes Feature completos
- [ ] Adicionar autenticação (Laravel Sanctum)
- [ ] Implementar rate limiting
- [ ] Adicionar validação de SKU duplicado em updates
- [ ] Melhorar tratamento de erros do Elasticsearch

### Médio Prazo

- [X] CI/CD com GitHub Actions
  - Lint (Laravel Pint)
  - Testes automatizados
  - Deploy automático
- [X] Processamento de imagens (resize, compress)
- [ ] Versionamento da API (v1, v2)
- [X] Documentação OpenAPI/Swagger
- [ ] Integração com SQS para eventos

### Longo Prazo

- [ ] Monitoramento e APM (New Relic/Datadog)
- [ ] Elasticsearch cluster multi-node
- [ ] CDN para imagens
- [ ] GraphQL endpoint
- [ ] Webhooks para eventos de produtos
- [ ] Admin panel (Laravel Nova/Filament)

---

## 📚 Documentação Adicional

### Arquivos Úteis

- `api.http` - Coleção de requisições HTTP para testes
- `commands.md` - Lista de comandos úteis
- `docker-compose.yml` - Configuração dos containers
- `phpunit.xml` - Configuração dos testes
- `CI_CD.md` - Documentação do pipeline CI/CD
- `AWS_S3.md` - Documentação da integração com AWS S3
- `TESTING.md` - Documentação completa dos testes

### Links Externos

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Scout](https://laravel.com/docs/scout)
- [Elasticsearch Guide](https://www.elastic.co/guide/en/elasticsearch/reference/current/index.html)
- [Redis Documentation](https://redis.io/documentation)
- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [GitHub Actions](https://docs.github.com/en/actions)

---

## 📄 Licença

Este projeto foi desenvolvido como desafio técnico.

---

## 👨‍💻 Cássio Gabriel

Desenvolvido com ☕ e 💻

---

## 🙏 Agradecimentos

Obrigado pela oportunidade de demonstrar minhas habilidades técnicas através deste desafio!
