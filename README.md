# Fotx

MVP Laravel para fotógrafos venderem fotos de eventos com busca por reconhecimento facial mockada.

## Stack

- Laravel 13
- PHP 8.5+ recomendado
- MySQL
- Laravel Breeze
- Livewire 4 + Tailwind
- Filas via `database` ou `redis`
- Storage local por padrão, preparado para Cloudflare R2/S3
- QR Code SVG para divulgação de eventos

## Instalação

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
php artisan queue:work
```

Com Laravel Herd, configure o app em:

```env
APP_URL=http://fotx.test
APP_TIMEZONE=America/Sao_Paulo
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=pt_BR
APP_FAKER_LOCALE=pt_BR
```

Para desenvolvimento com Vite:

```bash
npm run dev
```

Usuários de demo:

- Admin: `admin@fotx.test` / `password`
- Fotógrafo: `fotografo@fotx.test` / `password`
- Cliente: `cliente@fotx.test` / `password`

## Fluxo MVP

1. Fotógrafo cria e publica eventos em `/events`.
2. O painel do evento gera link público, QR Code SVG e cartaz imprimível.
3. Faz upload múltiplo em `/events/{event}/photos`.
4. `ProcessEventPhotoJob` gera thumbnail e versão com marca d'água.
5. Cliente acessa `/e/{slug}` pelo link ou QR Code.
6. Cliente envia selfie com aceite LGPD.
7. Cliente vê preview da selfie antes da busca.
8. `FaceRecognitionService` retorna fotos aleatórias com score mockado.
9. Cliente adiciona/remove fotos do carrinho e finaliza em `/checkout`.
10. O pedido nasce como `pending` e recebe uma URL de checkout.
11. Em desenvolvimento, a aprovação é simulada pelo gateway `mock`.
12. Em produção, o gateway preparado é `mercado_pago`.
13. A página de downloads é protegida por token e cada foto original usa link assinado temporário.

Clientes autenticados também podem acessar suas compras em:

```text
/my/photos
```

Fotógrafos acompanham pedidos e faturamento por evento em:

```text
/events/{event}/orders
```

Também há detalhe individual, exportação CSV, QR Code e cartaz:

```text
/events/{event}/orders/{order}
/events/{event}/orders/export
/events/{event}/qr-code.svg
/events/{event}/poster
```

## Reconhecimento facial

A busca por selfie usa drivers configuráveis em `App\Services\FaceRecognition`, escolhidos por `FACE_RECOGNITION_DRIVER`:

- `mock`: desenvolvimento; indexa um rosto fictício por foto e devolve fotos aleatórias. Bloqueado em produção.
- `rekognition`: Amazon Rekognition, com uma coleção de rostos por evento.

```env
FACE_RECOGNITION_DRIVER=rekognition
FACE_MATCH_THRESHOLD=90
FACE_SEARCH_MAX_RESULTS=50
REKOGNITION_REGION=sa-east-1
REKOGNITION_ACCESS_KEY_ID=
REKOGNITION_SECRET_ACCESS_KEY=
REKOGNITION_COLLECTION_PREFIX=
```

- `FACE_MATCH_THRESHOLD` é a semelhança mínima, em porcentagem, para uma foto aparecer no resultado.
- Sem chaves no `.env`, o SDK da AWS usa a cadeia padrão de credenciais (por exemplo, IAM role).
- O prefixo padrão das coleções é `fotx-<APP_ENV>`, para ambientes diferentes não compartilharem rostos.
- Configure na conta AWS a política de opt-out de uso de dados pelos serviços de IA (AWS Organizations), para que as imagens não sejam usadas no treinamento de modelos da AWS.

Fluxo:

1. Depois do processamento, `IndexPhotoFacesJob` envia ao provedor uma cópia JPEG redimensionada da foto original, sem marca d'água, e grava os rostos em `photo_faces`.
2. A busca envia a selfie e devolve só as fotos prontas com semelhança acima do limite, com a melhor pontuação por foto.
3. Selfie sem rosto ou falha do provedor marcam a busca como `failed` e exibem mensagem ao cliente.
4. Excluir uma foto remove os rostos dela no provedor; excluir um evento remove a coleção inteira.

O job tenta de novo com espera crescente, porque o Rekognition limita chamadas por segundo (5 por padrão em São Paulo). Para indexar fotos já enviadas ou reindexar depois de trocar de driver:

```bash
php artisan fotx:index-faces {id-ou-slug-do-evento}
```

## Segurança do MVP

- Clientes não acessam a área `/events`.
- Admin e fotógrafo acessam gestão de eventos.
- Downloads exigem pedido pago, foto comprada, token do pedido e link assinado temporário.
- Paths originais não são expostos publicamente.
- Selfies de busca expiram por `FACE_SELFIE_TTL_HOURS`.
- Buscas por selfie têm limite por evento/IP/sessão via `FACE_SEARCH_MAX_ATTEMPTS`.

## LGPD e limpeza de selfies

As buscas faciais salvam `expires_at` e podem ser limpas com:

```bash
php artisan fotx:purge-expired-selfies
```

Para simular sem remover:

```bash
php artisan fotx:purge-expired-selfies --dry-run
```

O scheduler roda essa limpeza diariamente às `03:15`. Em produção, mantenha o scheduler do Laravel ativo:

```bash
php artisan schedule:work
```

## Pagamentos

O checkout usa uma abstração em `App\Services\Payments`.

Modo local:

```env
PAYMENT_GATEWAY=mock
```

Produção com Mercado Pago Checkout Pro:

```env
PAYMENT_GATEWAY=mercado_pago
MERCADO_PAGO_ACCESS_TOKEN=
MERCADO_PAGO_PUBLIC_KEY=
MERCADO_PAGO_INTEGRATOR_ID=
```

Fluxo atual:

1. Checkout cria `orders.status = pending`.
2. Gateway cria `payment_reference` e `payment_checkout_url`.
3. Mock permite aprovar manualmente.
4. Mercado Pago usará webhook em `/payments/mercado-pago/webhook`.
5. Downloads só são liberados quando o pedido vira `paid`.

## Storage

O disk padrão vem de `FILESYSTEM_DISK`. Para local, os arquivos ficam privados em `storage/app/private` e são servidos por rotas controladas.

Para produção, o Fotx está preparado para Cloudflare R2 usando o driver S3-compatible do Laravel. Instale e mantenha a dependência:

```bash
composer require league/flysystem-aws-s3-v3
```

Configuração recomendada para R2:

```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_REGION=auto
R2_BUCKET=fotx
R2_URL=
R2_ENDPOINT=https://<account_id>.r2.cloudflarestorage.com
R2_USE_PATH_STYLE_ENDPOINT=false
```

Notas:

- deixe originais, thumbnails, watermarked e selfies privados no bucket;
- o app continua servindo previews por rotas controladas;
- downloads originais passam pela rota protegida do Laravel;
- quando o volume crescer, o próximo passo é upload direto/resumível para R2 com URL assinada.

O disk `s3` genérico continua disponível para AWS S3 ou outro provedor compatível:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=
AWS_URL=
AWS_ENDPOINT=
AWS_USE_PATH_STYLE_ENDPOINT=false
```

## Testes

```bash
php artisan test
```
