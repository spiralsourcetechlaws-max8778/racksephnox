#!/bin/bash
set -e

# ============================================================
# RACKSEPHNOX - INDUSTRIAL DB SETUP
# Fixed path: /root/racksephnox
# Auto-detects project root, environment, and configures everything
# ============================================================

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'

# ---------- 1. DETECT PROJECT ROOT ----------
PROJECT_ROOT="$(pwd)"
while [ ! -f "$PROJECT_ROOT/artisan" ]; do
    if [ "$PROJECT_ROOT" = "/" ]; then
        echo -e "${RED}❌ Could not find Laravel project root (artisan not found).${NC}"
        exit 1
    fi
    PROJECT_ROOT="$(dirname "$PROJECT_ROOT")"
done

echo -e "${BLUE}📂 Project root detected: $PROJECT_ROOT${NC}"
cd "$PROJECT_ROOT"

# ---------- 2. DETECT ENVIRONMENT ----------
if [ -d "/data/data/com.termux" ]; then
    ENV="termux"
elif [ -f "/etc/os-release" ]; then
    ENV="ubuntu"
else
    ENV="generic"
fi
echo -e "${BLUE}🌍 Environment: $ENV${NC}"

# ---------- 3. DATABASE PATH ----------
DB_PATH="$PROJECT_ROOT/database/database.sqlite"
echo -e "${BLUE}🗄️  Database path: $DB_PATH${NC}"

# ---------- 4. ENSURE .env EXISTS ----------
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env not found. Creating from .env.example...${NC}"
    if [ -f ".env.example" ]; then
        cp .env.example .env
    else
        echo -e "${RED}❌ No .env or .env.example found. Please run Step 1 first.${NC}"
        exit 1
    fi
fi

# Force DB_DATABASE to the absolute path
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_PATH|" .env

# ---------- 5. CREATE SQLITE FILE ----------
mkdir -p "$(dirname "$DB_PATH")"
touch "$DB_PATH"
chmod 666 "$DB_PATH"

# ---------- 6. INSTALL COMPOSER DEPENDENCIES ----------
if [ ! -f "vendor/autoload.php" ]; then
    echo -e "${YELLOW}📦 Installing Composer dependencies (no scripts first)...${NC}"
    composer install --no-interaction --prefer-dist --no-scripts
fi

# ---------- 7. GENERATE APP KEY ----------
if ! grep -q "^APP_KEY=base64:" .env; then
    echo -e "${YELLOW}🔑 Generating application key...${NC}"
    php artisan key:generate --force
fi

# ---------- 8. FIX PDO DEPRECATION WARNING ----------
if [ -f "config/database.php" ]; then
    sed -i 's/PDO::MYSQL_ATTR_SSL_CA/Pdo\\Mysql::ATTR_SSL_CA/g' config/database.php
fi

# ---------- 9. PREPARE STORAGE ----------
mkdir -p storage/framework/{sessions,views,cache}
chmod -R 777 storage/framework

# ---------- 10. MOVE DUPLICATE / BULK MIGRATIONS ----------
mkdir -p backup_migrations
for m in \
    2025_05_06_100001_create_trading_pairs_table.php \
    2025_05_05_000002_create_trade_orders_table.php \
    2026_03_30_182801_create_trade_orders_table.php \
    2026_03_17_214657_create_all_tables.php ; do
    [ -f "database/migrations/$m" ] && mv "database/migrations/$m" backup_migrations/
done

# ---------- 11. CREATE BOOT-TIME TABLES ----------
sqlite3 "$DB_PATH" <<'SQL'
CREATE TABLE IF NOT EXISTS trading_pairs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT UNIQUE NOT NULL,
    base_currency TEXT NOT NULL,
    quote_currency TEXT NOT NULL,
    min_trade_amount DECIMAL(15,8) DEFAULT 0.0001,
    max_trade_amount DECIMAL(15,8) DEFAULT 100,
    tick_size DECIMAL(15,8) DEFAULT 0.0001,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
INSERT OR IGNORE INTO trading_pairs (symbol, base_currency, quote_currency) VALUES ('BTCUSDT', 'BTC', 'USDT');

CREATE TABLE IF NOT EXISTS trade_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pair_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    side TEXT NOT NULL,
    status TEXT NOT NULL,
    limit_price DECIMAL(15,2),
    created_at DATETIME,
    updated_at DATETIME
);
SQL

# ---------- 12. RUN POST-AUTOLOAD SCRIPTS ----------
echo -e "${YELLOW}🔄 Running post-autoload scripts...${NC}"
composer run-script post-autoload-dump || true

# ---------- 13. CREATE CORE TABLES ----------
sqlite3 "$DB_PATH" <<'SQL'
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    phone TEXT,
    email_verified_at DATETIME,
    password TEXT NOT NULL,
    two_factor_secret TEXT,
    two_factor_recovery_codes TEXT,
    two_factor_confirmed_at DATETIME,
    is_admin BOOLEAN DEFAULT 0,
    kyc_level TEXT DEFAULT 'basic',
    is_verified BOOLEAN DEFAULT 0,
    remember_token TEXT,
    referral_code TEXT,
    referred_by INTEGER,
    kyc_status TEXT DEFAULT 'pending',
    is_active BOOLEAN DEFAULT 1,
    onboarding_completed BOOLEAN DEFAULT 0,
    avatar TEXT,
    notification_preferences TEXT,
    free_spins_available INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS wallets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    locked_balance DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_wallets_user_id ON wallets(user_id);

CREATE TABLE IF NOT EXISTS investment_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    min_amount DECIMAL(15,2) NOT NULL,
    max_amount DECIMAL(15,2) NOT NULL,
    daily_interest_rate DECIMAL(5,2) NOT NULL,
    duration_days INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS investments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    daily_profit DECIMAL(15,2) NOT NULL,
    total_projected_profit DECIMAL(15,2) NOT NULL,
    remaining_days INTEGER NOT NULL,
    status TEXT DEFAULT 'active',
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    last_accrued_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES investment_plans(id)
);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    wallet_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) NOT NULL,
    description TEXT,
    reference TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS mpesa_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    transaction_type TEXT NOT NULL,
    transaction_id TEXT,
    amount DECIMAL(15,2) NOT NULL,
    phone TEXT NOT NULL,
    reference TEXT NOT NULL,
    description TEXT,
    status TEXT DEFAULT 'pending',
    mpesa_receipt_number TEXT,
    transaction_date DATETIME,
    raw_callback_data TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS kyc_documents (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    document_type TEXT NOT NULL,
    document_path TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    verified_at DATETIME,
    rejection_reason TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    ip_address TEXT,
    user_agent TEXT,
    old_values TEXT,
    new_values TEXT,
    url TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS crypto_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    price_usd DECIMAL(20,8) NOT NULL,
    price_kes DECIMAL(20,2) NOT NULL,
    last_updated DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS trading_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    username TEXT UNIQUE NOT NULL,
    is_public BOOLEAN DEFAULT 1,
    allow_copy_trading BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS followed_traders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    follower_id INTEGER NOT NULL,
    trader_id INTEGER NOT NULL,
    copy_ratio DECIMAL(5,2) DEFAULT 1,
    auto_copy BOOLEAN DEFAULT 1,
    max_copy_amount DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trader_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS machine_investments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    machine_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    profit_credited DECIMAL(15,2) DEFAULT 0,
    status TEXT DEFAULT 'active',
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lottery_tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    prize_pool DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS trading_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    locked_balance DECIMAL(15,2) DEFAULT 0,
    btc_balance DECIMAL(15,8) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_user_id ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_last_activity ON sessions(last_activity);

CREATE TABLE IF NOT EXISTS cache (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL,
    expiration INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS jobs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    queue TEXT NOT NULL,
    payload TEXT NOT NULL,
    attempts INTEGER NOT NULL,
    reserved_at INTEGER,
    available_at INTEGER NOT NULL,
    created_at INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email TEXT PRIMARY KEY,
    token TEXT NOT NULL,
    created_at DATETIME
);

CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tokenable_type TEXT NOT NULL,
    tokenable_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    token TEXT UNIQUE NOT NULL,
    abilities TEXT,
    last_used_at DATETIME,
    expires_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
SQL

# ---------- 14. RUN MIGRATIONS ----------
echo -e "${YELLOW}🗃️ Running migrations...${NC}"
php artisan migrate --force || true

# ---------- 15. RUN SEEDERS ----------
echo -e "${YELLOW}🌱 Running seeders...${NC}"
php artisan db:seed --force || true

# ---------- 16. PATCH AUTO-VERIFY ON REGISTRATION ----------
PHP_FILE="app/Http/Controllers/Auth/RegisteredUserController.php"
if [ -f "$PHP_FILE" ] && ! grep -q "email_verified_at = now()" "$PHP_FILE"; then
    sed -i '/\$user = User::create/,/event(new Registered(\$user))/ {
        s/event(new Registered(\$user));/    \$user->email_verified_at = now();\n    \$user->save();\n\n    event(new Registered(\$user));/
    }' "$PHP_FILE"
fi

# ---------- 17. ENSURE ADMIN USER ----------
php artisan tinker --execute="
if (!\App\Models\User::where('email','admin@racksephnox.com')->exists()) {
    \App\Models\User::create([
        'name' => 'Super Admin',
        'email' => 'admin@racksephnox.com',
        'phone' => '+254711111111',
        'password' => bcrypt('admin123'),
        'is_admin' => 1,
        'is_verified' => 1,
        'kyc_status' => 'verified',
        'email_verified_at' => now(),
    ]);
    echo '✅ Admin user created.';
}
" || true

# ---------- 18. CLEAR & OPTIMIZE CACHES ----------
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ---------- 19. FRONTEND BUILD (OPTIONAL) ----------
if [ -f "package.json" ]; then
    echo -e "${YELLOW}📦 Installing and building frontend assets...${NC}"
    npm install --legacy-peer-deps || true
    npm run build || true
fi

# ---------- 20. FINAL SUMMARY ----------
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ Racksephnox setup complete!${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}📂 Project root: $PROJECT_ROOT${NC}"
echo -e "${BLUE}🗄️  Database path: $DB_PATH${NC}"
echo -e "${BLUE}🌍 Environment: $ENV${NC}"
echo ""
echo -e "${GREEN}🔐 Login credentials:${NC}"
echo "   Email:    admin@racksephnox.com"
echo "   Password: admin123"
echo ""
echo -e "${YELLOW}🚀 Start the server:${NC}"
echo "   php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo -e "${BLUE}📝 View logs:${NC}"
echo "   tail -f storage/logs/laravel.log"
echo ""
echo -e "${YELLOW}🛑 Stop services:${NC}"
echo "   pkill -f 'php artisan'"
echo "═══════════════════════════════════════════════════════════════"
