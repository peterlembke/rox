# AI Folders — Multi-Workspace Support for rox

## Context

Run multiple Claude Code sessions simultaneously, each on a different branch/task. The Docker environment mounts the entire project root at `/var/www`, so folders placed inside the project are automatically visible to the containers.

Three permanent folders (`ai1/`, `ai2/`, `ai3/`) inside the project root, each served by Apache on its own domain (`ai1.dev.local`, `ai2.dev.local`, `ai3.dev.local`). The rox command auto-detects which workspace you're in and adjusts paths accordingly.

How the folders are created is up to each project — rox only provides the web server config and auto-detection.

## Files to Modify

### 1. `rox/images/web/default.conf`

Add three VirtualHost blocks after the existing `dev.local` block. Apache silently ignores a VirtualHost whose DocumentRoot doesn't exist yet:

```apache
<VirtualHost *:80>
    ServerName ai1.dev.local
    DocumentRoot "/var/www/ai1/public"
    Timeout 600
    <Directory "/var/www/ai1">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://appserver:9000/var/www/ai1/public/$1
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
    ErrorLog ${APACHE_LOG_DIR}/ai1-error.log
    CustomLog ${APACHE_LOG_DIR}/ai1-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName ai2.dev.local
    DocumentRoot "/var/www/ai2/public"
    Timeout 600
    <Directory "/var/www/ai2">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://appserver:9000/var/www/ai2/public/$1
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
    ErrorLog ${APACHE_LOG_DIR}/ai2-error.log
    CustomLog ${APACHE_LOG_DIR}/ai2-access.log combined
</VirtualHost>

<VirtualHost *:80>
    ServerName ai3.dev.local
    DocumentRoot "/var/www/ai3/public"
    Timeout 600
    <Directory "/var/www/ai3">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    ProxyPassMatch ^/(.*\.php(/.*)?)$ fcgi://appserver:9000/var/www/ai3/public/$1
    SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
    ErrorLog ${APACHE_LOG_DIR}/ai3-error.log
    CustomLog ${APACHE_LOG_DIR}/ai3-access.log combined
</VirtualHost>
```

All on port 80 — Apache routes by `Host` header (name-based virtual hosting). No changes to `docker-compose.yml` needed since `default.conf` is already mounted.

### 2. `rox/main.sh`

**a) Add workspace auto-detection** — after config loading (after line 28), add the detect function and call it:

```bash
workspace_detect() {
    local project_root="$COMPOSE_DIR/.."
    local cwd="$(pwd)"
    local relative="${cwd#$project_root/}"
    local top_dir="${relative%%/*}"

    if [[ "$top_dir" =~ ^ai[1-3]$ ]] && [ -d "$project_root/$top_dir" ]; then
        ROX_WORKSPACE_NAME="$top_dir"
        ROX_BASE_DIR="/var/www/$top_dir"
    fi
}
workspace_detect
```

If inside an ai* folder, this overrides `ROX_BASE_DIR` so all existing commands (`rox artisan`, `rox composer`, `rox unit`, etc.) just work without any other changes.

### 3. `.gitignore`

Add:
```
/ai1
/ai2
/ai3
```

### 4. `/etc/hosts` (manual)

Add these entries:
```
127.0.0.1   ai1.dev.local
127.0.0.1   ai2.dev.local
127.0.0.1   ai3.dev.local
```

## Shared vs Independent

| Resource | Shared or Independent | How |
|---|---|---|
| Git history | Independent | Separate clone |
| Source code | Independent | Each clone has own files |
| vendor/ | Independent | `composer install` per workspace |
| packages/ | Independent | Part of the clone |
| .env | Independent | Copied with adjusted APP_URL |
| storage/ | Shared | Symlink to main `../storage` |
| Database (MariaDB) | Shared | Same container, same DB |
| Cache (Dragonfly) | Shared | Same container |
| MongoDB | Shared | Same container |

## Notes

- The three workspaces (ai1, ai2, ai3) are fixed. Apache config is committed and always present. A workspace folder that doesn't exist yet causes no errors.
- Database migrations are shared across all workspaces. If two branches have conflicting migrations, adjust `DB_DATABASE` in the workspace's `.env` to use a separate database.
