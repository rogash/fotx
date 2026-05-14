# Fotx

MVP Laravel para fotógrafos venderem fotos de eventos com busca por reconhecimento facial mockada.

## Stack

- Laravel 13
- PHP 8.5+ recomendado
- MySQL
- Laravel Breeze
- Livewire 4 + Tailwind
- Filas via `database` ou `redis`
- Storage local por padrão, preparado para S3

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
2. Faz upload múltiplo em `/events/{event}/photos`.
3. `ProcessEventPhotoJob` gera thumbnail e versão com marca d'água.
4. Cliente acessa `/e/{slug}`.
5. Cliente envia selfie com aceite LGPD.
6. Cliente vê preview da selfie antes da busca.
7. `FaceRecognitionService` retorna fotos aleatórias com score mockado.
8. Cliente adiciona/remove fotos do carrinho e finaliza em `/checkout`.
9. Pagamento é simulado como aprovado.
10. Downloads ficam em links protegidos por token em `/orders/{order}/downloads/{download_token}`.

Clientes autenticados também podem acessar suas compras em:

```text
/my/photos
```

Fotógrafos acompanham pedidos e faturamento por evento em:

```text
/events/{event}/orders
```

## Reconhecimento facial

O serviço real ainda não foi implementado. A classe `App\Services\FaceRecognitionService` já isola a integração futura e documenta o endpoint planejado:

```text
POST http://127.0.0.1:8001/search-face
```

## Segurança do MVP

- Clientes não acessam a área `/events`.
- Admin e fotógrafo acessam gestão de eventos.
- Downloads exigem pedido pago, foto comprada e token do pedido.
- Paths originais não são expostos publicamente.

## Storage

O disk padrão vem de `FILESYSTEM_DISK`. Para local, os arquivos ficam privados em `storage/app/private` e são servidos por rotas controladas. Para S3, configure:

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
