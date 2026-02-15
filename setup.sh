
# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}Iniciando setup do projeto Laravel Catalog API${NC}"

# Verificar se Docker está instalado
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker não encontrado. Por favor, instale o Docker primeiro.${NC}"
    exit 1
fi

# Verificar se Docker Compose está instalado
if ! command -v docker-compose &> /dev/null; then
    echo -e "${RED}Docker Compose não encontrado. Por favor, instale o Docker Compose primeiro.${NC}"
    exit 1
fi

# Criar arquivo .env se não existir
if [ ! -f .env ]; then
    echo -e "${YELLOW}Criando arquivo .env a partir do .env.example${NC}"
    cp .env.example .env
fi

# Parar containers existentes
echo -e "${YELLOW}Parando containers existentes...${NC}"
docker-compose down -v

# Construir e iniciar containers
echo -e "${YELLOW}Construindo e iniciando containers...${NC}"
docker-compose up -d --build

# Aguardar MySQL ficar pronto
echo -e "${YELLOW}Aguardando MySQL ficar pronto...${NC}"
sleep 10

# Instalar dependências do Composer
echo -e "${YELLOW}Instalando dependências do Composer...${NC}"
docker-compose exec app composer install

# Gerar chave da aplicação
echo -e "${YELLOW}Gerando chave da aplicação...${NC}"
docker-compose exec app php artisan key:generate

# Rodar migrations
echo -e "${YELLOW}Rodando migrations...${NC}"
docker-compose exec app php artisan migrate

# Rodar seeders
echo -e "${YELLOW}Rodando seeders...${NC}"
docker-compose exec app php artisan db:seed

# Criar índice no Elasticsearch
echo -e "${YELLOW}Criando índice no Elasticsearch...${NC}"
docker-compose exec app php artisan elastic:create-index

# Instalar dependências NPM (se necessário)
# echo -e "${YELLOW}Instalando dependências NPM...${NC}"
# docker-compose exec app npm install

# Compilar assets (se necessário)
# echo -e "${YELLOW}Compilando assets...${NC}"
# docker-compose exec app npm run dev

# Verificar status dos containers
echo -e "${GREEN}Setup concluído! Status dos containers:${NC}"
docker-compose ps

# Mostrar informações de acesso
echo -e "\n${GREEN}✅ Aplicação disponível em:${NC}"
echo -e "  • API: ${YELLOW}http://localhost${NC}"
echo -e "  • Adminer: ${YELLOW}http://localhost:8080${NC}"
echo -e "  • Kibana: ${YELLOW}http://localhost:5601${NC}"
echo -e "  • Mailhog: ${YELLOW}http://localhost:8025${NC}"
echo -e "\n${GREEN}📦 Serviços:${NC}"
echo -e "  • MySQL: ${YELLOW}localhost:3306${NC}"
echo -e "  • Redis: ${YELLOW}localhost:6379${NC}"
echo -e "  • Elasticsearch: ${YELLOW}localhost:9200${NC}"
echo -e "\n${GREEN}🔧 Comandos úteis:${NC}"
echo -e "  • docker-compose logs -f [service]  : Ver logs"
echo -e "  • docker-compose exec app bash       : Acessar container"
echo -e "  • docker-compose down                 : Parar containers"
echo -e "  • docker-compose up -d                 : Iniciar containers"
