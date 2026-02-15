# ☁️ AWS S3 - Upload de Imagens

Documentação completa da integração com AWS S3 para upload de imagens de produtos.

## 📋 Índice

- [Visão Geral](#visão-geral)
- [Configuração](#configuração)
- [Uso](#uso)
- [Arquitetura](#arquitetura)
- [Fallback](#fallback)
- [Segurança](#segurança)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Visão Geral

O sistema de upload de imagens suporta:
- ✅ Upload para AWS S3
- ✅ Fallback para storage local
- ✅ Processamento assíncrono de imagens
- ✅ Múltiplos tamanhos (thumbnail, medium, large)
- ✅ Otimização automática
- ✅ URLs públicas

### Fluxo de Upload

```
1. Cliente envia imagem
   ↓
2. Validação (tipo, tamanho)
   ↓
3. Upload para S3 (ou local)
   ↓
4. Job processa imagem (resize, otimização)
   ↓
5. Gera múltiplos tamanhos
   ↓
6. Retorna URL pública
```

---

## ⚙️ Configuração

### 1. Instalar Dependências

```bash
# AWS SDK para PHP
composer require league/flysystem-aws-s3-v3 "^3.0"

# Intervention Image (processamento)
composer require intervention/image
```

### 2. Configurar Variáveis de Ambiente

Adicione no `.env`:

```env
# Filesystem
FILESYSTEM_DISK=s3

# AWS S3
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### 3. Criar Bucket no AWS S3

#### Via AWS Console

1. Acesse [S3 Console](https://s3.console.aws.amazon.com)
2. Clique em "Create bucket"
3. Configure:
   - **Bucket name**: `catalog-api-products`
   - **Region**: `us-east-1`
   - **Block Public Access**: Desabilitar (para URLs públicas)
   - **Versioning**: Opcional
4. Clique em "Create bucket"

#### Via AWS CLI

```bash
# Criar bucket
aws s3 mb s3://catalog-api-products --region us-east-1

# Configurar política pública
aws s3api put-bucket-policy --bucket catalog-api-products --policy file://bucket-policy.json
```

**bucket-policy.json**:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "PublicReadGetObject",
      "Effect": "Allow",
      "Principal": "*",
      "Action": "s3:GetObject",
      "Resource": "arn:aws:s3:::catalog-api-products/*"
    }
  ]
}
```

### 4. Criar IAM User

1. Acesse [IAM Console](https://console.aws.amazon.com/iam)
2. Crie usuário: `catalog-api-s3-user`
3. Anexe política:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket"
      ],
      "Resource": [
        "arn:aws:s3:::catalog-api-products",
        "arn:aws:s3:::catalog-api-products/*"
      ]
    }
  ]
}
```

4. Gere Access Key
5. Copie credenciais para `.env`

---

## 💻 Uso

### Upload de Imagem

#### Via API

```bash
curl -X POST http://localhost/api/products/1/image \
  -H "Content-Type: multipart/form-data" \
  -F "image=@product.jpg"
```

#### Resposta

```json
{
  "success": true,
  "message": "Imagem enviada com sucesso",
  "data": {
    "image_url": "https://catalog-api-products.s3.amazonaws.com/products/uuid.jpg"
  }
}
```

### Validações

- **Tipos aceitos**: jpeg, png, jpg, gif
- **Tamanho máximo**: 2MB
- **Dimensões**: Sem limite (será redimensionada)

### Tamanhos Gerados

| Tamanho | Largura | Uso |
|---------|---------|-----|
| Thumbnail | 150px | Listagens |
| Medium | 500px | Detalhes |
| Large | 1200px | Zoom |
| Original | - | Backup |

---

## 🏗️ Arquitetura

### Componentes

#### 1. StorageService

**Localização**: `app/Services/StorageService.php`

Responsável por:
- Upload de arquivos
- Fallback automático
- Gerenciamento de disks
- Verificação de configuração

**Métodos**:
```php
upload(UploadedFile $file, string $path, string $disk = null): array
delete(string $path, string $disk = null): bool
exists(string $path, string $disk = null): bool
url(string $path, string $disk = null): ?string
isS3Configured(): bool
getStorageInfo(): array
```

#### 2. ProductService

**Localização**: `app/Services/ProductService.php`

Método `uploadImage()`:
- Recebe imagem
- Usa StorageService
- Atualiza produto
- Retorna URL

#### 3. ProcessProductImage Job

**Localização**: `app/Jobs/ProcessProductImage.php`

Processa imagem assincronamente:
- Cria múltiplos tamanhos
- Otimiza qualidade
- Upload para S3
- Logs detalhados

### Fluxo Detalhado

```
POST /api/products/{id}/image
  ↓
ProductController::uploadImage()
  ↓
ProductService::uploadImage()
  ↓
StorageService::upload()
  ↓
[Tenta S3] → [Sucesso] → Retorna URL
  ↓
[Falha S3] → [Fallback Local] → Retorna URL
  ↓
ProcessProductImage Job (assíncrono)
  ↓
Gera tamanhos (thumbnail, medium, large)
  ↓
Upload para S3
  ↓
Logs e notificações
```

---

## 🔄 Fallback

### Quando Ocorre

O fallback para storage local ocorre quando:
- AWS credentials não configuradas
- Bucket não existe
- Sem permissões
- Timeout de conexão
- Erro de rede

### Como Funciona

```php
try {
    // Tenta upload para S3
    $result = Storage::disk('s3')->put($path, $file);
} catch (\Exception $e) {
    // Fallback para local
    Log::warning('S3 failed, using local storage');
    $result = Storage::disk('public')->put($path, $file);
}
```

### Configuração de Fallback

```env
# Usar S3 como padrão
FILESYSTEM_DISK=s3

# Se S3 falhar, usa 'public' automaticamente
```

### Verificar Status

```bash
curl http://localhost/api/health
```

Resposta:
```json
{
  "status": "healthy",
  "services": {
    "storage": {
      "status": "up",
      "default_disk": "s3",
      "s3_configured": true,
      "s3_available": true
    }
  }
}
```

---

## 🔒 Segurança

### Boas Práticas

#### 1. Credenciais

- ❌ Nunca commitar credenciais
- ✅ Usar variáveis de ambiente
- ✅ Rotacionar keys regularmente
- ✅ Usar IAM roles em produção

#### 2. Permissões

- ✅ Princípio do menor privilégio
- ✅ Apenas ações necessárias
- ✅ Restringir por bucket
- ❌ Não usar root credentials

#### 3. Bucket

- ✅ Habilitar versioning
- ✅ Configurar lifecycle policies
- ✅ Habilitar logging
- ✅ Configurar CORS se necessário

#### 4. Validação

```php
// Validar tipo de arquivo
$request->validate([
    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
]);

// Validar conteúdo (não apenas extensão)
$mimeType = $file->getMimeType();
if (!in_array($mimeType, ['image/jpeg', 'image/png', 'image/gif'])) {
    throw new \Exception('Invalid file type');
}
```

### CORS Configuration

Se acessar S3 diretamente do frontend:

```json
[
  {
    "AllowedHeaders": ["*"],
    "AllowedMethods": ["GET", "PUT", "POST"],
    "AllowedOrigins": ["https://seu-dominio.com"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
}
```

---

## 🐛 Troubleshooting

### Erro: "Credentials not found"

**Problema**: AWS credentials não configuradas

**Solução**:
```bash
# Verificar .env
cat .env | grep AWS

# Configurar
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
```

### Erro: "Bucket does not exist"

**Problema**: Bucket não existe ou nome incorreto

**Solução**:
```bash
# Listar buckets
aws s3 ls

# Criar bucket
aws s3 mb s3://catalog-api-products
```

### Erro: "Access Denied"

**Problema**: Sem permissões no bucket

**Solução**:
1. Verificar IAM policy
2. Verificar bucket policy
3. Verificar ACLs

### Erro: "Connection timeout"

**Problema**: Firewall ou rede bloqueando

**Solução**:
1. Verificar firewall
2. Verificar security groups
3. Testar conectividade: `curl https://s3.amazonaws.com`

### Upload Lento

**Problema**: Upload demora muito

**Soluções**:
1. Usar CloudFront CDN
2. Habilitar Transfer Acceleration
3. Otimizar imagem antes de upload
4. Usar multipart upload para arquivos grandes

---

## 📊 Monitoramento

### Logs

```bash
# Ver logs de upload
tail -f storage/logs/laravel.log | grep "upload"

# Ver logs de S3
tail -f storage/logs/laravel.log | grep "S3"
```

### Métricas

Monitorar no AWS CloudWatch:
- Número de requests
- Latência
- Erros 4xx/5xx
- Bytes transferidos
- Custo

### Alertas

Configurar alertas para:
- Taxa de erro > 5%
- Latência > 2s
- Custo mensal > threshold
- Quota de storage > 80%

---

## 💰 Custos

### Estimativa AWS S3

| Item | Preço (us-east-1) |
|------|-------------------|
| Storage | $0.023/GB/mês |
| PUT requests | $0.005/1000 |
| GET requests | $0.0004/1000 |
| Data transfer OUT | $0.09/GB |

### Exemplo

Para 10.000 produtos com 3 imagens cada:
- Storage: 30.000 imagens × 500KB = 15GB = $0.35/mês
- Uploads: 30.000 × $0.005/1000 = $0.15
- Downloads: 100.000 views × $0.0004/1000 = $0.04
- **Total**: ~$0.54/mês

### Otimização de Custos

1. **Lifecycle Policies** - Mover para Glacier após 90 dias
2. **Intelligent-Tiering** - Otimização automática
3. **CloudFront** - Reduzir data transfer
4. **Compression** - Reduzir tamanho de arquivos

---

## 🎯 Próximos Passos

- [ ] Implementar CloudFront CDN
- [ ] Adicionar watermark em imagens
- [ ] Implementar signed URLs
- [ ] Adicionar suporte a vídeos
- [ ] Implementar backup automático
- [ ] Adicionar compressão WebP
- [ ] Implementar lazy loading
- [ ] Adicionar analytics de uso

---

## 📚 Recursos

- [AWS S3 Documentation](https://docs.aws.amazon.com/s3/)
- [Laravel Filesystem](https://laravel.com/docs/filesystem)
- [Flysystem AWS S3](https://flysystem.thephpleague.com/docs/adapter/aws-s3/)
- [Intervention Image](http://image.intervention.io/)

---

**Última atualização**: 2026-02-14  
**Versão**: 1.0.0  
**Status**: ✅ Implementado
