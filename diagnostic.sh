#!/bin/bash

echo "==================================="
echo "🔍 DIAGNÓSTICO COMPLETO DO SISTEMA"
echo "==================================="
echo

echo "📦 VERSÕES:"
echo "Docker: $(docker --version)"
echo "Docker Compose: $(docker-compose --version)"
echo

echo "🔧 STATUS DOS CONTAINERS:"
docker-compose ps
echo

echo "🌐 PORTAS EM USO:"
sudo netstat -tlnp | grep -E ":(80|3306|6379|9200|5601|8080|8025)" || echo "Nenhuma porta encontrada"
echo

echo "🔄 TESTANDO SERVIÇOS:"
echo -n "MySQL: "
docker-compose exec mysql mysqladmin ping -h localhost -u laravel -proot --silent > /dev/null 2>&1 && echo "✅ OK" || echo "❌ FALHOU"

echo -n "Redis: "
docker-compose exec redis redis-cli ping 2>/dev/null | grep -q PONG && echo "✅ OK" || echo "❌ FALHOU"

echo -n "Elasticsearch: "
curl -s http://localhost:9200 > /dev/null && echo "✅ OK" || echo "❌ FALHOU"

echo -n "Nginx: "
curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q 200 && echo "✅ OK" || echo "❌ FALHOU"
echo

echo "📝 LOGS DOS SERVIÇOS (últimas 5 linhas):"
echo "--- APP ---"
docker-compose logs --tail=5 app 2>/dev/null || echo "Sem logs"
echo
echo "--- REDIS ---"
docker-compose logs --tail=5 redis 2>/dev/null || echo "Sem logs"
echo
echo "--- ELASTICSEARCH ---"
docker-compose logs --tail=5 elasticsearch 2>/dev/null || echo "Sem logs"
echo

echo "==================================="
