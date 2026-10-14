# Deploying to Render.com with Docker

This guide explains how to deploy this Laravel Advertisement System server to [Render.com](https://render.com) using Docker.

---

## What Was Configured

1. **[Dockerfile](file:///./Dockerfile)**:
   - **Multi-stage build**:
     - Stage 1 compiles Tailwind CSS and JavaScript assets using Node 20 & Vite (`npm run build`).
     - Stage 2 runs a lean `php:8.3-fpm-alpine` container with Nginx and Supervisor.
   - Includes all required PHP extensions: `pdo`, `pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`, `gd` (with JPEG & FreeType support), `bcmath`, `intl`, `zip`, `opcache`, `pcntl`, `exif`.
   - Optimized autoloader (`composer install --no-dev --optimize-autoloader`).
2. **[docker/entrypoint.sh](file:///./docker/entrypoint.sh)**:
   - Automatically adapts Nginx to Render's dynamic `$PORT` environment variable.
   - Sets correct directory permissions on `storage` and `bootstrap/cache`.
   - Runs `php artisan storage:link --force`.
   - Initializes SQLite if SQLite is selected.
   - Executes database migrations automatically when `RUN_MIGRATIONS=true`.
   - Caches configuration, routes, and views for production performance.
3. **[docker/nginx.conf](file:///./docker/nginx.conf)**:
   - Configured for up to 64MB uploads (ad images and videos).
   - Serves the `/up` healthcheck for Render zero-downtime deploys.
   - Configures static asset caching and FastCGI buffers.
4. **[docker/supervisord.conf](file:///./docker/supervisord.conf)**:
   - Manages both Nginx and PHP-FPM processes, outputting logs to stdout/stderr for Render's live log viewer.
5. **[render.yaml](file:///./render.yaml)**:
   - Render Blueprint for 1-click deployment.
6. **[bootstrap/app.php](file:///./bootstrap/app.php)**:
   - Configured `trustProxies(at: '*')` so SSL termination on Render works cleanly without Mixed-Content HTTPS issues.

---

## Deployment Option 1: Using Render Dashboard (Recommended)

### Step 1: Push Code to GitHub / GitLab
Commit and push your changes to your repository:
```bash
git add .
git commit -m "Add Docker configuration for Render.com"
git push origin main
```

### Step 2: Create a New Web Service on Render
1. Log in to [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** -> **Web Service**.
3. Connect your Git repository (`advertisementsystemserver`).
4. Select **Docker** as the environment (Render will automatically detect your `Dockerfile`).
5. Choose your Region (e.g. *Oregon*, *Frankfurt*, etc.) and Instance Type (Free or Starter).

### Step 3: Configure Environment Variables
Under the **Environment** tab in Render, configure the following:

| Key | Suggested Value | Description |
|---|---|---|
| `APP_NAME` | `AdvertisementSystem` | App display name |
| `APP_ENV` | `production` | Laravel environment |
| `APP_DEBUG` | `false` | Disable debug mode in production |
| `APP_KEY` | *(Click "Generate" or run `php artisan key:generate --show`)* | 32-character application key |
| `APP_URL` | `https://your-service-name.onrender.com` | Your Render public URL |
| `LOG_CHANNEL` | `stderr` | Sends logs to Render Live Logs |
| `LOG_LEVEL` | `info` | Minimum log level |
| `RUN_MIGRATIONS` | `true` | Automatically runs `php artisan migrate --force` on deploy |
| `DB_CONNECTION` | `sqlite` *(or `pgsql`)* | Database driver |
| `AD_SYSTEM_API_KEY` | *(Your custom secret string)* | Header `X-Ad-Api-Key` used by Unity client |
| `MODERATOR_DEFAULT_PASSWORD` | *(Set strong password)* | Password for default admin account (`admin@adsystem.local`) |
| `PAYSTACK_PUBLIC_KEY` | `pk_live_...` or `pk_test_...` | Paystack public key |
| `PAYSTACK_SECRET_KEY` | `sk_live_...` or `sk_test_...` | Paystack secret key |
| `PAYSTACK_MERCHANT_EMAIL` | `your-email@domain.com` | Paystack merchant email |

### Step 4: Health Check Path
Set **Health Check Path** to:
```
/up
```
*(Render will ping `/up` to verify the container is healthy before routing live traffic).*

### Step 5: Deploy
Click **Create Web Service**. Render will pull the repository, run the multi-stage Docker build, and start the service.

---

## Deployment Option 2: Render Blueprint (`render.yaml`)

1. In Render Dashboard, click **New +** -> **Blueprint**.
2. Select your repository.
3. Render reads [render.yaml](file:///./render.yaml) and automatically creates the web service and auto-generates secure random keys for `APP_KEY`, `AD_SYSTEM_API_KEY`, etc.
4. Input your `PAYSTACK_PUBLIC_KEY` and `PAYSTACK_SECRET_KEY` in the prompt, then click **Apply**.

---

## Database & Media Storage Considerations on Render

### 1. Database
- **SQLite (Free tier)**: Runs inside the container at `/var/www/html/database/database.sqlite`. On the free tier, files are ephemeral and reset on redeploy.
- **Render PostgreSQL (Recommended for production)**:
  1. In Render, create a **New +** -> **PostgreSQL**.
  2. Set `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT=5432`, `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in your Web Service environment.

### 2. Uploaded Ad Images and Videos
- On Render free tier, files stored in `storage/app/public` are ephemeral.
- On Render paid plans, you can attach a **Persistent Disk** (e.g. mount `/var/www/html/storage/app/public`).
- Alternatively, for production scale, you can configure Laravel's S3 filesystem driver (`FILESYSTEM_DISK=s3`) using AWS S3, Cloudflare R2, or DigitalOcean Spaces.

---

## Default Login Credentials
Once the initial migration completes, the default moderator account is:
- **Email**: `admin@adsystem.local`
- **Password**: Value of `MODERATOR_DEFAULT_PASSWORD` (or `admin123` if not overridden)
