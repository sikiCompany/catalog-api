# Comandos Úteis

## Docker
- `docker-compose up -d` - Iniciar containers
- `docker-compose down` - Parar containers
- `docker-compose down -v` - Parar e remover volumes
- `docker-compose logs -f app` - Ver logs da aplicação
- `docker-compose exec app bash` - Acessar container

## Laravel
- `php artisan migrate` - Rodar migrations
- `php artisan migrate:fresh --seed` - Resetar banco e seed
- `php artisan make:model ModelName -m` - Criar model com migration
- `php artisan tinker` - Tinker (REPL)
- `php artisan test` - Rodar testes

## Elasticsearch
- `curl http://localhost:9200/_cat/indices` - Listar índices
- `curl http://localhost:9200/products/_search` - Buscar produtos

## Redis
- `docker-compose exec redis redis-cli` - Acessar Redis CLI
- `docker-compose exec redis redis-cli FLUSHALL` - Limpar cache

## MySQL
- `docker-compose exec mysql mysql -u laravel -p` - Acessar MySQL
