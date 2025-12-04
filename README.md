# HostForge - Webhosting Billing Platform

Een professioneel webhosting billing platform gebouwd met Laravel 11, Vue.js 3 en integraties met Plesk, OpenProvider en Moneybird.

## 🚀 Features

- **Public Order Pagina**: Klanten kunnen hosting pakketten en domeinnamen bestellen
- **Admin Panel**: Beheer klanten, orders en provisioning
- **API Integraties**:
  - **Plesk API**: Automatische provisioning van users en domeinen
  - **OpenProvider API**: Domeinregistratie
  - **Moneybird API**: Automatische facturering
- **Security First**: Multi-stage Docker builds, non-root containers, security headers
- **Kubernetes Ready**: Complete k3d/Kubernetes manifests met auto-scaling

## 📋 Requirements

- Docker & Docker Compose
- k3d (voor Kubernetes deployment)
- PHP 8.2+
- PostgreSQL 16
- Redis 7
- Node.js 20+

## 🛠️ Tech Stack

- **Backend**: Laravel 11 (PHP 8.2)
- **Frontend**: Vue.js 3 met Vite
- **Database**: PostgreSQL 16
- **Cache/Queue**: Redis 7
- **Container Runtime**: Docker + Kubernetes (k3d)
- **CI/CD**: GitHub Actions → GHCR

## 📦 Installation

### Option 1: Docker Compose (Development)

```bash
# Clone het project
git clone <repository-url>
cd ddd

# Kopieer .env.example naar .env
cp .env.example .env

# Genereer applicatie key
docker-compose run --rm app php artisan key:generate

# Start de containers
docker-compose up -d

# Run migraties
docker-compose exec app php artisan migrate

# Installeer frontend dependencies en build
docker-compose exec app npm install
docker-compose exec app npm run build

# Applicatie is beschikbaar op http://localhost:8000
```

### Option 2: Kubernetes met k3d (Production-like)

```bash
# Installeer k3d (als nog niet geïnstalleerd)
curl -s https://raw.githubusercontent.com/k3d-io/k3d/main/install.sh | bash

# Deploy naar k3d
./k8s/deploy.sh

# Voeg toe aan /etc/hosts
echo "127.0.0.1 hostforge.local" | sudo tee -a /etc/hosts

# Applicatie is beschikbaar op http://hostforge.local:8080
```

## 🔧 Configuration

### Environment Variables

Update `.env` of `k8s/02-secrets.yaml` met je API credentials:

```bash
# Plesk API
PLESK_HOST=your-plesk-host.com
PLESK_USERNAME=admin
PLESK_PASSWORD=your-secure-password

# OpenProvider API
OPENPROVIDER_USERNAME=your-username
OPENPROVIDER_PASSWORD=your-password

# Moneybird API
MONEYBIRD_API_TOKEN=your-api-token
MONEYBIRD_ADMINISTRATION_ID=your-admin-id
```

### GitHub Secrets (voor CI/CD)

Voeg de volgende secrets toe aan je GitHub repository:

- `GHCR_TOKEN`: GitHub Container Registry token
- `PLESK_HOST`, `PLESK_USERNAME`, `PLESK_PASSWORD`
- `OPENPROVIDER_USERNAME`, `OPENPROVIDER_PASSWORD`
- `MONEYBIRD_API_TOKEN`, `MONEYBIRD_ADMINISTRATION_ID`

## 🏗️ Architecture

### Database Schema

- `customers`: Klantgegevens met Plesk/Moneybird IDs
- `hosting_packages`: Beschikbare hosting pakketten
- `orders`: Bestellingen met status tracking
- `domains`: Geregistreerde domeinen met OpenProvider/Plesk IDs

### Workflow

1. **Order Plaatsen**: Klant bestelt via public webpagina
2. **Admin Approval**: Admin beoordeelt en accepteert order
3. **Provisioning**:
   - Plesk: User + domein aanmaken
   - OpenProvider: Domein registreren
   - Moneybird: Factuur aanmaken en versturen
4. **Activatie**: Order status → active

### Service Classes

- **PleskService**: Beheer Plesk customers en domains
- **OpenProviderService**: Domein registratie en management
- **MoneybirdService**: Contact en factuur management

## 🔒 Security Features

### Docker Security

✅ Multi-stage builds (kleiner, veiliger)
✅ Non-root user (UID 1000)
✅ Alpine Linux (minimale attack surface)
✅ Health checks
✅ Read-only root filesystem waar mogelijk
✅ Security headers (CSP, HSTS, X-Frame-Options)
✅ PHP hardening (disabled functions, no expose_php)

### Kubernetes Security

✅ Security contexts met runAsNonRoot
✅ Pod Security Standards
✅ Resource limits en quotas
✅ Network policies ready
✅ Secrets management
✅ RBAC ready

### Application Security

✅ Rate limiting via middleware
✅ Input validation
✅ CSRF protection
✅ SQL injection protection (Eloquent ORM)
✅ XSS protection
✅ Secure session handling

## 📊 Monitoring & Scaling

### Health Checks

- HTTP: `GET /health`
- PHP-FPM: TCP check op poort 9000
- Database: PostgreSQL ready checks

### Auto-scaling

HorizontalPodAutoscaler configuratie:
- Min replicas: 2
- Max replicas: 10
- CPU target: 70%
- Memory target: 80%

## 🧪 Testing

```bash
# Unit tests
docker-compose exec app php artisan test

# Code style
docker-compose exec app ./vendor/bin/pint

# Static analysis (installeer eerst)
docker-compose exec app composer require --dev phpstan/phpstan
docker-compose exec app ./vendor/bin/phpstan analyse
```

## 📝 API Endpoints

### Public API

- `POST /api/orders`: Plaats een nieuwe order
- `GET /api/packages`: Beschikbare hosting pakketten
- `POST /api/domains/check`: Check domein beschikbaarheid

### Admin API (authentication required)

- `GET /api/admin/customers`: Lijst klanten
- `POST /api/admin/customers/{id}/approve`: Accepteer klant
- `GET /api/admin/orders`: Lijst orders
- `POST /api/admin/orders/{id}/provision`: Provision order
- `POST /api/admin/invoices`: Maak factuur

## 🚀 Deployment

### Build Docker Image

```bash
docker build -t ghcr.io/angelosleebos/hostforge:latest .
docker push ghcr.io/angelosleebos/hostforge:latest
```

### Deploy naar Kubernetes

```bash
# Update image in k8s/06-app-deployment.yaml
# Dan:
kubectl apply -f k8s/

# Of gebruik het deploy script:
./k8s/deploy.sh production
```

## 🐛 Troubleshooting

### Logs bekijken

```bash
# Docker Compose
docker-compose logs -f app

# Kubernetes
kubectl logs -f -l app=hostforge,component=app -n hostforge

# Specifieke pod
kubectl logs -f <pod-name> -n hostforge
```

### Database migraties issues

```bash
# Docker Compose
docker-compose exec app php artisan migrate:fresh --seed

# Kubernetes
kubectl exec -it <app-pod> -n hostforge -- php artisan migrate
```

### Permission issues

```bash
# Fix storage permissions
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R hostforge:hostforge storage bootstrap/cache
```

## 📚 Development

### Code Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/         # Admin controllers
│   │   └── Api/           # Public API
│   └── Middleware/        # Custom middleware
├── Models/                # Eloquent models
├── Services/              # External API services
└── Jobs/                  # Queue jobs

resources/
├── js/
│   ├── components/        # Vue components
│   └── pages/            # Vue pages
└── views/                # Blade templates

k8s/                      # Kubernetes manifests
docker/                   # Docker configs
```

### Adding New Features

1. Create migration: `php artisan make:migration create_x_table`
2. Create model: `php artisan make:model X`
3. Create controller: `php artisan make:controller XController`
4. Add routes in `routes/api.php` or `routes/web.php`
5. Create Vue component in `resources/js/components/`
6. Run tests: `php artisan test`

## 📄 License

Proprietary - All rights reserved

## 👥 Contributors

- Angelo Sleebos

## 🆘 Support

Voor vragen of problemen:
- Email: support@hostforge.nl
- Issues: GitHub Issues

---

Built with ❤️ by the HostForge team
