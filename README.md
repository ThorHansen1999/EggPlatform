<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Docker Stack (PostgreSQL + Redis)

This project includes a Docker setup for local development:

Services:
- app (PHP-FPM + Laravel) – built from multi-stage Dockerfile.
- nginx – serves public/ and proxies PHP-FPM.
- postgres – primary database (16-alpine).
- redis – cache + queue backend.
- horizon – Laravel Horizon dashboard + managed queue workers.
- scheduler – runs `schedule:run` every minute.
- pgadmin (optional, enable with profile `tools`).

### Quick Start
```powershell
# Build and start (dev stage)
docker compose up -d --build

# View logs
docker compose logs -f app

# Run artisan commands
docker compose exec app php artisan migrate
```

### Scaling Workers (Horizon)
```powershell
# Scale queue workers
docker compose up -d --scale horizon=3
```
Horizon auto-balances processes configured in `config/horizon.php`.

### Horizon Dashboard
Accessible at: `http://localhost:8080/horizon`
(Adjust `HORIZON_PATH` env if you change the path.)

### Environment Notes
- `.env` is bind-mounted; update DB/Redis credentials there for tests.
- For production: build with `target=php-base`, remove bind mounts, pre-run caches:
```powershell
docker build -t eggplatform-app:prod --target=php-base .
```

### Regenerating APP_KEY
- Auto-generated by entrypoint if missing locally.
- For production, set `APP_KEY` explicitly via secrets or env injection.

### Common Tasks
```powershell
# Run tests
docker compose exec app php artisan test

# Tinker
docker compose exec app php artisan tinker
```

### Validation (manual)
After containers start, visit http://localhost:8080/ to confirm app responds "ok".

### Cleanup
```powershell
docker compose down -v
```

### Production Build (Optimized)
Use the new prod stage which pre-caches config, routes, views, events:
```powershell
docker build -t eggplatform-app:prod --target=prod .
```
Run with a minimal compose override (example):
```powershell
echo "services:
  app:
    build:
      context: .
      target: prod
    image: eggplatform-app:prod
    restart: unless-stopped
    volumes: []
" > compose.prod.yml

docker compose -f docker-compose.yml -f compose.prod.yml up -d --build
```
Notes:
- No healthcheck directives; rely on external probes or orchestrator.
- Set real secrets via env/secret manager; do not bake them into image.
- Remove dev-only services (pgadmin, scheduler loop) if not needed.
