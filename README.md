# Keycloak Deployment with Docker Compose

This repository contains a Docker Compose setup for deploying Keycloak with automatic SSL certificate management using nginx-certbot.

## Services

- **Keycloak**: Version 26.4.2 from quay.io/keycloak/keycloak
- **PostgreSQL**: Database backend for Keycloak (Alpine 16)
- **nginx-certbot**: Reverse proxy with automatic Let's Encrypt SSL certificates (jonasal/nginx-certbot:5.4.0)

## Prerequisites

- Docker and Docker Compose installed
- Domain name `devsso.swinnertonsolutions.com` pointing to your server's IP address
- Ports 80 and 443 open on your firewall

## Setup Instructions

1. **Clone or navigate to this directory**

2. **Create environment file**
   ```bash
   cp .env.example .env
   ```

3. **Edit the .env file**
   ```bash
   nano .env
   ```

   Update the following values:
   - `POSTGRES_PASSWORD`: Strong password for PostgreSQL
   - `KEYCLOAK_ADMIN_PASSWORD`: Strong password for Keycloak admin user
   - `CERTBOT_EMAIL`: Your email for Let's Encrypt notifications

4. **Ensure DNS is configured**
   Make sure `devsso.swinnertonsolutions.com` resolves to your server's IP address:
   ```bash
   nslookup devsso.swinnertonsolutions.com
   ```

5. **Start the services**
   ```bash
   docker compose up -d
   ```

6. **Monitor the logs**
   ```bash
   docker compose logs -f
   ```

   Wait for:
   - PostgreSQL to be ready
   - Keycloak to start and migrate the database
   - nginx-certbot to obtain SSL certificates

## Access Keycloak

Once deployed, access Keycloak at:
- URL: `https://devsso.swinnertonsolutions.com`
- Admin Console: `https://devsso.swinnertonsolutions.com/admin`
- Username: Value of `KEYCLOAK_ADMIN` from .env (default: admin)
- Password: Value of `KEYCLOAK_ADMIN_PASSWORD` from .env

## SSL Certificate Notes

- The first startup will take longer as Let's Encrypt certificates are obtained
- Certificates will automatically renew every 12 hours
- Certificate files are stored in the `nginx_secrets` Docker volume

## Troubleshooting

**Certificate generation fails:**
- Verify DNS is correctly configured
- Check if ports 80 and 443 are accessible from the internet
- Review nginx logs: `docker compose logs nginx`

**Keycloak won't start:**
- Check database connection: `docker compose logs postgres`
- Verify Keycloak logs: `docker compose logs keycloak`
- Ensure sufficient resources (2GB+ RAM recommended)

**Cannot access Keycloak:**
- Check nginx logs: `docker compose logs nginx`
- Verify all services are running: `docker compose ps`
- Test internal connectivity: `docker compose exec nginx curl http://keycloak:8080`

## Maintenance

**View logs:**
```bash
docker compose logs -f [service_name]
```

**Restart services:**
```bash
docker compose restart [service_name]
```

**Stop services:**
```bash
docker compose down
```

**Stop and remove volumes (WARNING: This deletes all data):**
```bash
docker compose down -v
```

**Backup PostgreSQL database:**
```bash
docker compose exec postgres pg_dump -U keycloak keycloak > backup.sql
```

**Restore PostgreSQL database:**
```bash
cat backup.sql | docker compose exec -T postgres psql -U keycloak keycloak
```

## Security Recommendations

1. Change default passwords in .env file
2. Regularly update Docker images
3. Monitor logs for suspicious activity
4. Configure firewall to restrict access to ports 80 and 443 only
5. Enable Keycloak security features (MFA, password policies, etc.)
6. Regular database backups

## File Structure

```
.
├── docker-compose.yml          # Main Docker Compose configuration
├── .env                        # Environment variables (create from .env.example)
├── .env.example                # Example environment file
├── nginx/
│   └── conf.d/
│       └── keycloak.conf       # Nginx reverse proxy configuration
└── README.md                   # This file
```
