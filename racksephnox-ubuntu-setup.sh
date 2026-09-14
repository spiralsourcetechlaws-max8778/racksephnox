#!/bin/bash
set -e

# ============================================================
# RACKSEPHNOX - SETUP FOR UBUNTU PROOT (Termux)
# SKIPS PHP INSTALLATION (assumed already present)
# ============================================================

# ---------- COLOR CODES ----------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Racksephnox Setup for Ubuntu Proot (PHP already installed)${NC}"

# ---------- 1. UPDATE SYSTEM & INSTALL CORE TOOLS ----------
echo -e "${YELLOW}📦 Updating system and installing core tools...${NC}"
apt update -y && apt upgrade -y
apt install -y git curl wget unzip zip nano sqlite3 software-properties-common

# ---------- 2. INSTALL COMPOSER ----------
echo -e "${YELLOW}📥 Installing Composer...${NC}"
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

# ---------- 3. INSTALL NODE.JS 20 LTS ----------
echo -e "${YELLOW}🟩 Installing Node.js 20 LTS...${NC}"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs

# ---------- 4. CLONE / PREPARE PROJECT ----------
echo -e "${YELLOW}📂 Cloning or preparing project...${NC}"
cd ~
if [ -d "RackTechSephnox" ]; then
    echo -e "${YELLOW}⚠️  RackTechSephnox already exists. Removing to start fresh...${NC}"
    rm -rf RackTechSephnox
fi
git clone -b develop https://github.com/racks624/racksephnox.git RackTechSephnox
cd RackTechSephnox/racksephnox

PROJECT_ROOT="$(pwd)"
DB_PATH="$PROJECT_ROOT/database/database.sqlite"

# ---------- 5. CREATE .ENV ----------
echo -e "${YELLOW}📝 Creating .env file...${NC}"
cat > .env <<EOF
APP_NAME=Racksephnox
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite
DB_DATABASE=$DB_PATH
DB_FOREIGN_KEYS=true

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
BROADCAST_DRIVER=log

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@racksephnox.com"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

# ---------- 6. GENERATE APP KEY ----------
php artisan key:generate --force

# ---------- 7. CREATE SQLITE DATABASE ----------
mkdir -p "$(dirname "$DB_PATH")"
touch "$DB_PATH"
chmod 666 "$DB_PATH"

# ---------- 8. FIX PDO DEPRECATION ----------
if [ -f "config/database.php" ]; then
    sed -i 's/PDO::MYSQL_ATTR_SSL_CA/Pdo\\Mysql::ATTR_SSL_CA/g' config/database.php
fi

# ---------- 9. CREATE SESSIONS TABLE ----------
php artisan session:table --force 2>/dev/null || true

# ---------- 10. CREATE BOOT-TIME TABLES ----------
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

# ---------- 11. MOVE DUPLICATE/BULK MIGRATIONS ----------
mkdir -p backup_migrations
for migration in 2025_05_06_100001_create_trading_pairs_table.php \
                 2025_05_05_000002_create_trade_orders_table.php \
                 2026_03_30_182801_create_trade_orders_table.php \
                 2026_03_17_214657_create_all_tables.php; do
    if [ -f "database/migrations/$migration" ]; then
        mv "database/migrations/$migration" backup_migrations/
    fi
done

# ---------- 12. CREATE ALL MISSING TABLES (FULL SCHEMA) ----------
sqlite3 "$DB_PATH" <<'SQL'
-- Users
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

-- Wallets
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

-- Investment Plans
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
CREATE INDEX IF NOT EXISTS idx_investment_plans_is_active ON investment_plans(is_active);

-- Investments
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
CREATE INDEX IF NOT EXISTS idx_investments_user_status ON investments(user_id, status);
CREATE INDEX IF NOT EXISTS idx_investments_end_date ON investments(end_date);

-- Transactions
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
CREATE INDEX IF NOT EXISTS idx_transactions_user_created ON transactions(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_transactions_reference ON transactions(reference);

-- M-Pesa Transactions
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
CREATE INDEX IF NOT EXISTS idx_mpesa_transactions_status ON mpesa_transactions(status);
CREATE INDEX IF NOT EXISTS idx_mpesa_transactions_reference ON mpesa_transactions(reference);
CREATE INDEX IF NOT EXISTS idx_mpesa_transactions_mpesa_receipt_number ON mpesa_transactions(mpesa_receipt_number);

-- KYC Documents
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
CREATE INDEX IF NOT EXISTS idx_kyc_documents_user_status ON kyc_documents(user_id, status);

-- Audit Logs
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
CREATE INDEX IF NOT EXISTS idx_audit_logs_user_created ON audit_logs(user_id, created_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_action ON audit_logs(action);

-- Crypto Prices
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
CREATE INDEX IF NOT EXISTS idx_crypto_prices_last_updated ON crypto_prices(last_updated);

-- Trading Profiles
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

-- Followed Traders
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

-- Machine Investments
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

-- Lottery Tournaments
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
SQL

# ---------- 13. RUN MIGRATIONS ----------
php artisan migrate --force

# ---------- 14. RUN SEEDERS ----------
php artisan db:seed --force

# ---------- 15. ADD MISSING COLUMNS TO USERS ----------
sqlite3 "$DB_PATH" <<'SQL' 2>/dev/null || true
ALTER TABLE users ADD COLUMN referral_code TEXT;
ALTER TABLE users ADD COLUMN referred_by INTEGER NULL;
ALTER TABLE users ADD COLUMN kyc_status TEXT DEFAULT 'pending';
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT 1;
ALTER TABLE users ADD COLUMN two_factor_secret TEXT;
ALTER TABLE users ADD COLUMN two_factor_recovery_codes TEXT;
ALTER TABLE users ADD COLUMN two_factor_confirmed_at DATETIME;
ALTER TABLE users ADD COLUMN kyc_level TEXT DEFAULT 'basic';
ALTER TABLE users ADD COLUMN is_verified BOOLEAN DEFAULT 0;
ALTER TABLE users ADD COLUMN onboarding_completed BOOLEAN DEFAULT 0;
ALTER TABLE users ADD COLUMN avatar TEXT;
ALTER TABLE users ADD COLUMN notification_preferences TEXT;
ALTER TABLE users ADD COLUMN free_spins_available INTEGER DEFAULT 0;
SQL

# ---------- 16. MAKE PHONE NULLABLE ----------
sqlite3 "$DB_PATH" "ALTER TABLE users RENAME TO users_old;" 2>/dev/null || true
sqlite3 "$DB_PATH" <<'SQL' 2>/dev/null || true
CREATE TABLE IF NOT EXISTS users_new (
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
INSERT OR IGNORE INTO users_new SELECT * FROM users_old;
DROP TABLE users_old;
ALTER TABLE users_new RENAME TO users;
SQL

# ---------- 17. AUTO-VERIFY USERS ON REGISTRATION ----------
PHP_FILE="app/Http/Controllers/Auth/RegisteredUserController.php"
if [ -f "$PHP_FILE" ]; then
    if ! grep -q "email_verified_at = now()" "$PHP_FILE"; then
        sed -i '/$user = User::create/,/event(new Registered($user))/ {
            s/event(new Registered($user));/    $user->email_verified_at = now();\n    $user->save();\n\n    event(new Registered($user));/
        }' "$PHP_FILE"
    fi
fi

# ---------- 18. FIX TRADING ACCOUNTS ----------
sqlite3 "$DB_PATH" "DROP TABLE IF EXISTS trading_accounts;" 2>/dev/null || true
sqlite3 "$DB_PATH" <<'SQL' 2>/dev/null || true
CREATE TABLE trading_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    balance DECIMAL(15,2) DEFAULT 0,
    locked_balance DECIMAL(15,2) DEFAULT 0,
    btc_balance DECIMAL(15,8) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
INSERT OR IGNORE INTO trading_accounts (user_id, created_at, updated_at) VALUES (1, datetime('now'), datetime('now'));
SQL

# ---------- 19. SET PERMISSIONS ----------
mkdir -p storage/framework/{sessions,views,cache}
chmod -R 777 storage bootstrap/cache

# ---------- 20. CLEAR & OPTIMIZE CACHES ----------
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ---------- 21. NPM INSTALL & BUILD (if package.json exists) ----------
if [ -f "package.json" ]; then
    npm install --legacy-peer-deps
    npm run build || echo -e "${YELLOW}⚠️  Build failed (optional). You can run 'npm run dev' later.${NC}"
fi

# ---------- 22. FINAL MESSAGE ----------
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ Racksephnox is fully set up and ready for production!${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}📂 Project root: $PROJECT_ROOT${NC}"
echo -e "${BLUE}🗄️  Database path: $DB_PATH${NC}"
echo -e "${BLUE}🌍 Environment: Ubuntu Proot${NC}"
echo ""
echo -e "${GREEN}🔐 Login credentials:${NC}"
echo "   Email: admin@racksephnox.com"
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
