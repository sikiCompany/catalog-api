#!/bin/bash

# Script helper para executar testes
# Uso: ./test.sh [opção]

set -e

# Cores para output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}==================================${NC}"
echo -e "${BLUE}  Catalog API - Test Runner${NC}"
echo -e "${BLUE}==================================${NC}"
echo ""

# Função para executar comando no container
run_test() {
    docker-compose exec app php artisan test "$@"
}

# Verifica se o container está rodando
if ! docker-compose ps | grep -q "laravel-app.*Up"; then
    echo -e "${YELLOW}⚠️  Container não está rodando. Iniciando...${NC}"
    docker-compose up -d
    echo -e "${GREEN}✓ Container iniciado${NC}"
    echo ""
fi

# Parse de argumentos
case "${1:-all}" in
    all)
        echo -e "${GREEN}Executando todos os testes...${NC}"
        run_test
        ;;
    
    feature)
        echo -e "${GREEN}Executando testes Feature...${NC}"
        run_test --testsuite=Feature
        ;;
    
    unit)
        echo -e "${GREEN}Executando testes Unit...${NC}"
        run_test --testsuite=Unit
        ;;
    
    coverage)
        echo -e "${GREEN}Executando testes com cobertura...${NC}"
        run_test --coverage
        ;;
    
    verbose)
        echo -e "${GREEN}Executando testes com output detalhado...${NC}"
        run_test --verbose
        ;;
    
    filter)
        if [ -z "$2" ]; then
            echo -e "${YELLOW}Uso: ./test.sh filter <nome_do_teste>${NC}"
            exit 1
        fi
        echo -e "${GREEN}Executando testes filtrados por: $2${NC}"
        run_test --filter="$2"
        ;;
    
    file)
        if [ -z "$2" ]; then
            echo -e "${YELLOW}Uso: ./test.sh file <caminho_do_arquivo>${NC}"
            exit 1
        fi
        echo -e "${GREEN}Executando arquivo: $2${NC}"
        run_test "$2"
        ;;
    
    product)
        echo -e "${GREEN}Executando testes de Product...${NC}"
        run_test tests/Feature/ProductApiTest.php tests/Unit/ProductTest.php
        ;;
    
    search)
        echo -e "${GREEN}Executando testes de Search...${NC}"
        run_test tests/Feature/SearchApiTest.php
        ;;
    
    cache)
        echo -e "${GREEN}Executando testes de Cache...${NC}"
        run_test tests/Feature/ProductCacheTest.php tests/Unit/CacheableTraitTest.php
        ;;
    
    quick)
        echo -e "${GREEN}Executando testes rápidos (sem cobertura)...${NC}"
        run_test --stop-on-failure
        ;;
    
    help|--help|-h)
        echo "Uso: ./test.sh [opção]"
        echo ""
        echo "Opções disponíveis:"
        echo "  all         - Executa todos os testes (padrão)"
        echo "  feature     - Executa apenas testes Feature"
        echo "  unit        - Executa apenas testes Unit"
        echo "  coverage    - Executa testes com relatório de cobertura"
        echo "  verbose     - Executa testes com output detalhado"
        echo "  filter      - Filtra testes por nome (ex: ./test.sh filter test_can_create)"
        echo "  file        - Executa arquivo específico (ex: ./test.sh file tests/Unit/ProductTest.php)"
        echo "  product     - Executa testes relacionados a Product"
        echo "  search      - Executa testes relacionados a Search"
        echo "  cache       - Executa testes relacionados a Cache"
        echo "  quick       - Executa testes parando no primeiro erro"
        echo "  help        - Mostra esta mensagem"
        echo ""
        echo "Exemplos:"
        echo "  ./test.sh"
        echo "  ./test.sh feature"
        echo "  ./test.sh filter test_can_create_product"
        echo "  ./test.sh file tests/Feature/ProductApiTest.php"
        ;;
    
    *)
        echo -e "${YELLOW}Opção inválida: $1${NC}"
        echo "Use './test.sh help' para ver as opções disponíveis"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}✓ Testes concluídos!${NC}"
