cat > /root/racksephnox/db-provision.sh <<'MASTERSCRIPT'
#!/bin/bash
set -e

# ============================================================
# RACKSEPHNOX — MASTER DATABASE PROVISIONER
# Complete schema for every domain of the Empire.
# Frequency: 888 Hz | φ = 1.61803398875 | λ = 1.27201964951
# ============================================================

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; MAGENTA='\033[0;35m'; NC='\033[0m'

# ---------- 1. DETECT PROJECT ROOT ----------
PROJECT_ROOT="$(pwd)"
while [ ! -f "$PROJECT_ROOT/artisan" ]; do
    if [ "$PROJECT_ROOT" = "/" ]; then
        echo -e "${RED}❌ Could not find Laravel project root (artisan not found).${NC}"
        exit 1
    fi
    PROJECT_ROOT="$(dirname "$PROJECT_ROOT")"
done
echo -e "${BLUE}📂 Project root: $PROJECT_ROOT${NC}"
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
echo -e "${BLUE}🗄️  Database: $DB_PATH${NC}"

# ---------- 4. PREPARE .ENV ----------
if [ ! -f ".env" ]; then
    echo -e "${YELLOW}⚠️  .env not found. Creating from .env.example...${NC}"
    [ -f ".env.example" ] && cp .env.example .env || {
        echo -e "${RED}❌ No .env or .env.example. Create one first.${NC}"; exit 1; }
fi

sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DB_PATH|" .env
grep -q "^DB_CONNECTION=" .env || echo "DB_CONNECTION=sqlite" >> .env
grep -q "^SESSION_DRIVER=" .env || echo "SESSION_DRIVER=database" >> .env
sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=database/' .env

# ---------- 5. CREATE DATABASE FILE ----------
mkdir -p "$(dirname "$DB_PATH")"
touch "$DB_PATH"
chmod 666 "$DB_PATH"

# ---------- 6. FIX PDO DEPRECATION ----------
if [ -f "config/database.php" ]; then
    sed -i 's/PDO::MYSQL_ATTR_SSL_CA/Pdo\\Mysql::ATTR_SSL_CA/g' config/database.php
fi

# ---------- 7. GENERATE APP KEY ----------
if ! grep -q "^APP_KEY=base64:" .env; then
    echo -e "${YELLOW}🔑 Generating APP_KEY...${NC}"
    php artisan key:generate --force
fi

# ---------- 8. INSTALL COMPOSER DEPS ----------
if [ ! -f "vendor/autoload.php" ]; then
    echo -e "${YELLOW}📦 Installing Composer dependencies...${NC}"
    composer install --no-interaction --prefer-dist --no-scripts
fi

# ---------- 9. MOVE CONFLICTING MIGRATIONS ----------
echo -e "${YELLOW}📦 Archiving migrations for tables we provision manually...${NC}"
mkdir -p backup_migrations

for pattern in \
    trading_pairs trade_orders trading_candles trading_accounts trading_profiles \
    trading_bonus machine machines lottery sessions cache jobs notifications \
    personal_access password_reset investment investments mpesa copy_trades \
    followed_traders guilds exchange_rates btc_price ; do
    for m in database/migrations/*create_*${pattern}*.php \
             database/migrations/*add_*${pattern}*.php \
             database/migrations/*enhance_*${pattern}*.php \
             database/migrations/*alter_*${pattern}*.php ; do
        [ -f "$m" ] && mv "$m" backup_migrations/ 2>/dev/null || true
    done
done

# Also move all "add_*" migrations for the users table since we provision it fully
for m in database/migrations/*add_*users*.php ; do
    [ -f "$m" ] && mv "$m" backup_migrations/ 2>/dev/null || true
done

echo -e "${GREEN}✅ Migrations archived.${NC}"

# ============================================================
# 10. CORE SCHEMA — CREATE ALL TABLES
# ============================================================
echo -e "${MAGENTA}════════════════════════════════════════════════${NC}"
echo -e "${MAGENTA}   PROVISIONING FULL RACKSEPHNOX SCHEMA${NC}"
echo -e "${MAGENTA}════════════════════════════════════════════════${NC}"

sqlite3 "$DB_PATH" <<'SQL'

PRAGMA foreign_keys = OFF;

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 1 · CORE (users, wallets, sessions, cache...)   ║
-- ╚══════════════════════════════════════════════════════════╝

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
    currency TEXT DEFAULT 'KES',
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_wallets_user ON wallets(user_id);

CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    wallet_id INTEGER,
    type TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance_after DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    reference TEXT,
    status TEXT DEFAULT 'completed',
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_transactions_user ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_ref ON transactions(reference);

CREATE TABLE IF NOT EXISTS sessions (
    id TEXT PRIMARY KEY,
    user_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    payload TEXT NOT NULL,
    last_activity INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_sessions_user ON sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_activity ON sessions(last_activity);

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

CREATE TABLE IF NOT EXISTS notifications (
    id TEXT PRIMARY KEY,
    type TEXT NOT NULL,
    notifiable_type TEXT NOT NULL,
    notifiable_id INTEGER NOT NULL,
    data TEXT NOT NULL,
    read_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_notifications_notifiable
    ON notifications(notifiable_type, notifiable_id);

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
    updated_at DATETIME
);

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 2 · BANKING (deposits, withdrawals, mpesa)      ║
-- ╚══════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS deposit_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    method TEXT DEFAULT 'mpesa',
    reference TEXT,
    phone TEXT,
    status TEXT DEFAULT 'pending',
    approved_by INTEGER,
    approved_at DATETIME,
    rejection_reason TEXT,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS withdrawal_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    method TEXT DEFAULT 'mpesa',
    account_details TEXT,
    status TEXT DEFAULT 'pending',
    processed_by INTEGER,
    processed_at DATETIME,
    rejection_reason TEXT,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS user_bank_accounts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    bank_name TEXT NOT NULL,
    account_name TEXT NOT NULL,
    account_number TEXT NOT NULL,
    branch TEXT,
    is_default BOOLEAN DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
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

CREATE TABLE IF NOT EXISTS crypto_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT UNIQUE NOT NULL,
    name TEXT NOT NULL,
    price_usd DECIMAL(20,8) NOT NULL,
    price_kes DECIMAL(20,2) NOT NULL,
    percent_change_24h DECIMAL(10,4) DEFAULT 0,
    last_updated DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS btc_price_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    price DECIMAL(20,8) NOT NULL,
    price_kes DECIMAL(20,2),
    high DECIMAL(20,8),
    low DECIMAL(20,8),
    volume DECIMAL(20,8),
    percent_change_24h DECIMAL(10,4) DEFAULT 0,
    recorded_at DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_btc_history_recorded ON btc_price_history(recorded_at);

CREATE TABLE IF NOT EXISTS btc_price_histories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    price DECIMAL(20,8) NOT NULL,
    price_kes DECIMAL(20,2),
    percent_change_24h DECIMAL(10,4) DEFAULT 0,
    recorded_at DATETIME NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS exchange_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_currency TEXT NOT NULL,
    to_currency TEXT NOT NULL,
    rate DECIMAL(15,6) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 3 · INVESTMENTS (plans + holdings)              ║
-- ╚══════════════════════════════════════════════════════════╝

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
CREATE INDEX IF NOT EXISTS idx_inv_plans_active ON investment_plans(is_active);

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
CREATE INDEX IF NOT EXISTS idx_investments_user ON investments(user_id, status);

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 4 · MACHINES (RX Series)                        ║
-- ╚══════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS machines (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    code TEXT NOT NULL,
    description TEXT,
    vip1_start_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    vip2_start_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    vip3_start_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    duration_days INTEGER DEFAULT 14,
    growth_rate DECIMAL(5,2) DEFAULT 25.00,
    is_active BOOLEAN DEFAULT 1,
    risk_profile TEXT DEFAULT 'Medium',
    icon TEXT DEFAULT 'fa-microchip',
    color TEXT DEFAULT 'from-gold-400 to-amber-400',
    min_daily_profit DECIMAL(15,2),
    max_daily_profit DECIMAL(15,2),
    referral_bonus_rate DECIMAL(5,2) DEFAULT 5,
    early_withdrawal_penalty DECIMAL(5,2) DEFAULT 20,
    features TEXT,
    total_invested_limit DECIMAL(15,2),
    compound_frequency INTEGER DEFAULT 1,
    min_withdrawal DECIMAL(15,2) DEFAULT 0,
    max_withdrawal DECIMAL(15,2),
    bonus_multiplier DECIMAL(5,2) DEFAULT 1,
    staking_reward DECIMAL(5,2) DEFAULT 0,
    tier_multiplier DECIMAL(5,2) DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);
CREATE INDEX IF NOT EXISTS idx_machines_code ON machines(code);

CREATE TABLE IF NOT EXISTS machine_vips (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    machine_id INTEGER,
    level INTEGER DEFAULT 1,
    vip_level INTEGER DEFAULT 1,
    name TEXT,
    start_amount DECIMAL(15,2) DEFAULT 0,
    max_amount DECIMAL(15,2),
    growth_rate DECIMAL(5,2) DEFAULT 25,
    duration_days INTEGER DEFAULT 14,
    daily_profit_min DECIMAL(15,2) DEFAULT 0,
    daily_profit_max DECIMAL(15,2) DEFAULT 0,
    bonus_multiplier DECIMAL(5,2) DEFAULT 1,
    referral_bonus_rate DECIMAL(5,2) DEFAULT 5,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_machine_vips_machine ON machine_vips(machine_id);

CREATE TABLE IF NOT EXISTS machine_investments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    machine_id INTEGER NOT NULL,
    vip_level INTEGER DEFAULT 1,
    amount DECIMAL(15,2) NOT NULL,
    daily_profit DECIMAL(15,2) DEFAULT 0,
    total_projected_profit DECIMAL(15,2) DEFAULT 0,
    profit_credited DECIMAL(15,2) DEFAULT 0,
    status TEXT DEFAULT 'active',
    start_date DATETIME,
    end_date DATETIME,
    last_accrued_at DATETIME,
    withdrawn BOOLEAN DEFAULT 0,
    early_withdrawn BOOLEAN DEFAULT 0,
    penalty_applied DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (machine_id) REFERENCES machines(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_machine_inv_user ON machine_investments(user_id);

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 5 · TRADING (pairs, orders, candles, profiles)  ║
-- ╚══════════════════════════════════════════════════════════╝

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

CREATE TABLE IF NOT EXISTS trade_orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    pair_id INTEGER NOT NULL,
    side TEXT NOT NULL,
    order_type TEXT DEFAULT 'market',
    amount_btc DECIMAL(15,8) NOT NULL,
    filled_amount DECIMAL(15,8) DEFAULT 0,
    limit_price DECIMAL(15,2),
    stop_price DECIMAL(15,2),
    price_per_btc DECIMAL(15,2),
    filled_kes DECIMAL(15,2) DEFAULT 0,
    take_profit DECIMAL(15,2),
    stop_loss DECIMAL(15,2),
    time_in_force TEXT DEFAULT 'GTC',
    status TEXT DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_trade_orders_user ON trade_orders(user_id, status);
CREATE INDEX IF NOT EXISTS idx_trade_orders_pair ON trade_orders(pair_id, side, status);

CREATE TABLE IF NOT EXISTS trading_candles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pair_id INTEGER NOT NULL,
    "interval" TEXT NOT NULL,
    open_time DATETIME NOT NULL,
    close_time DATETIME NOT NULL,
    open DECIMAL(20,8) NOT NULL,
    high DECIMAL(20,8) NOT NULL,
    low DECIMAL(20,8) NOT NULL,
    close DECIMAL(20,8) NOT NULL,
    volume DECIMAL(20,8) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (pair_id) REFERENCES trading_pairs(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_candles_pair ON trading_candles(pair_id, "interval", open_time);

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

CREATE TABLE IF NOT EXISTS trading_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    username TEXT UNIQUE NOT NULL,
    display_name TEXT,
    bio TEXT,
    avatar TEXT,
    is_public BOOLEAN DEFAULT 1,
    allow_copy_trading BOOLEAN DEFAULT 1,
    copy_ratio DECIMAL(5,2) DEFAULT 1,
    total_trades INTEGER DEFAULT 0,
    winning_trades INTEGER DEFAULT 0,
    total_profit DECIMAL(15,2) DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS trading_bonus_trackers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    bonus_type TEXT DEFAULT 'signup',
    bonus_amount DECIMAL(15,2) DEFAULT 0,
    required_volume DECIMAL(15,2) DEFAULT 0,
    achieved_volume DECIMAL(15,2) DEFAULT 0,
    is_claimed BOOLEAN DEFAULT 0,
    expires_at DATETIME,
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

CREATE TABLE IF NOT EXISTS copy_trades (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    original_order_id INTEGER NOT NULL,
    follower_id INTEGER NOT NULL,
    trader_id INTEGER NOT NULL,
    original_amount DECIMAL(15,8) NOT NULL,
    copied_amount DECIMAL(15,8) NOT NULL,
    original_price DECIMAL(15,2) NOT NULL,
    copied_kes DECIMAL(15,2) NOT NULL,
    side TEXT NOT NULL,
    status TEXT DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME
);

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 6 · LOTTERY (games, spins, tournaments, guilds) ║
-- ╚══════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS lottery_games (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    min_bet DECIMAL(15,2) DEFAULT 10,
    max_bet DECIMAL(15,2) DEFAULT 1000,
    ticket_price DECIMAL(15,2) DEFAULT 10,
    is_active BOOLEAN DEFAULT 1,
    settings TEXT,
    progressive_jackpot DECIMAL(15,2) DEFAULT 1000,
    jackpot_contribution_rate DECIMAL(5,2) DEFAULT 5,
    base_rtp DECIMAL(5,2) DEFAULT 95,
    vip_rtp DECIMAL(5,2) DEFAULT 97,
    promo_rtp DECIMAL(5,2) DEFAULT 99,
    volatility TEXT DEFAULT 'medium',
    reel_config TEXT,
    paylines TEXT,
    bonus_symbol_id INTEGER,
    free_spins_award INTEGER DEFAULT 0,
    enable_free_spins BOOLEAN DEFAULT 1,
    enable_bonus_buy BOOLEAN DEFAULT 1,
    bonus_buy_price DECIMAL(15,2) DEFAULT 100,
    max_daily_loss DECIMAL(15,2),
    max_weekly_loss DECIMAL(15,2),
    max_monthly_loss DECIMAL(15,2),
    max_win_cap DECIMAL(15,2),
    cool_down_minutes INTEGER,
    session_timeout_minutes INTEGER,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_symbols (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    display_name TEXT,
    icon TEXT,
    multiplier DECIMAL(10,2) DEFAULT 1,
    is_divine BOOLEAN DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_payouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lottery_game_id INTEGER NOT NULL,
    lottery_symbol_id INTEGER NOT NULL,
    count INTEGER NOT NULL,
    payout_multiplier DECIMAL(15,2) NOT NULL,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_spins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lottery_game_id INTEGER,
    bet_amount DECIMAL(15,2) NOT NULL,
    win_amount DECIMAL(15,2) DEFAULT 0,
    symbols TEXT,
    is_free_spin BOOLEAN DEFAULT 0,
    free_spin_used BOOLEAN DEFAULT 0,
    last_free_spin_at DATETIME,
    jackpot_won DECIMAL(15,2) DEFAULT 0,
    tax_paid DECIMAL(15,2) DEFAULT 0,
    provably_fair_seed TEXT,
    provably_fair_hash TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_spins_user ON lottery_spins(user_id, created_at);

CREATE TABLE IF NOT EXISTS lottery_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    requirement_type TEXT,
    requirement_value INTEGER,
    reward_amount DECIMAL(15,2) DEFAULT 0,
    icon TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_user_achievements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lottery_achievement_id INTEGER NOT NULL,
    achieved_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lottery_daily_streaks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    current_streak INTEGER DEFAULT 0,
    longest_streak INTEGER DEFAULT 0,
    last_spin_date DATE,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS lottery_tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    prize_pool DECIMAL(15,2) DEFAULT 0,
    prize_distributed BOOLEAN DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_tournament_entries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lottery_tournament_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    score INTEGER DEFAULT 0,
    rank INTEGER,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_missions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    requirement_type TEXT,
    requirement_value INTEGER,
    reward_amount DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_user_missions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lottery_mission_id INTEGER NOT NULL,
    progress INTEGER DEFAULT 0,
    completed BOOLEAN DEFAULT 0,
    claimed BOOLEAN DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_revenue_targets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    target_amount DECIMAL(15,2) NOT NULL,
    current_revenue DECIMAL(15,2) DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_bonus_wheels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    segments TEXT,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_bonus_wheel_spins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lottery_bonus_wheel_id INTEGER,
    reward_amount DECIMAL(15,2) DEFAULT 0,
    reward_type TEXT,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_guilds (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    owner_id INTEGER NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_guild_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lottery_guild_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT DEFAULT 'member',
    joined_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS lottery_guild_tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lottery_guild_id INTEGER NOT NULL,
    lottery_tournament_id INTEGER NOT NULL,
    score INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME
);

-- ╔══════════════════════════════════════════════════════════╗
-- ║  SECTION 7 · LOANS DOMAIN                                ║
-- ╚══════════════════════════════════════════════════════════╝

CREATE TABLE IF NOT EXISTS loan_products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    icon TEXT DEFAULT 'fa-hand-holding-usd',
    color TEXT DEFAULT 'from-gold-400 to-amber-500',
    min_amount DECIMAL(15,2) NOT NULL,
    max_amount DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    interest_method TEXT DEFAULT 'reducing',
    min_duration_days INTEGER NOT NULL DEFAULT 30,
    max_duration_days INTEGER NOT NULL DEFAULT 365,
    grace_period_days INTEGER DEFAULT 3,
    late_fee_rate DECIMAL(5,2) DEFAULT 5.00,
    requires_guarantor BOOLEAN DEFAULT 0,
    requires_collateral BOOLEAN DEFAULT 0,
    min_credit_score INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    frequency_hz INTEGER DEFAULT 528,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS loans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reference TEXT UNIQUE NOT NULL,
    user_id INTEGER NOT NULL,
    loan_product_id INTEGER NOT NULL,
    principal DECIMAL(15,2) NOT NULL,
    interest_rate DECIMAL(5,2) NOT NULL,
    interest_method TEXT DEFAULT 'reducing',
    total_interest DECIMAL(15,2) DEFAULT 0,
    total_payable DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    late_fees DECIMAL(15,2) DEFAULT 0,
    duration_days INTEGER NOT NULL,
    repayment_frequency TEXT DEFAULT 'monthly',
    installments INTEGER DEFAULT 1,
    installment_amount DECIMAL(15,2) DEFAULT 0,
    status TEXT DEFAULT 'pending',
    purpose TEXT,
    approved_by INTEGER,
    approved_at DATETIME,
    rejected_reason TEXT,
    disbursed_at DATETIME,
    first_due_date DATETIME,
    next_due_date DATETIME,
    last_payment_at DATETIME,
    closed_at DATETIME,
    credit_score_at_application INTEGER DEFAULT 0,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (loan_product_id) REFERENCES loan_products(id)
);
CREATE INDEX IF NOT EXISTS idx_loans_user ON loans(user_id);
CREATE INDEX IF NOT EXISTS idx_loans_status ON loans(status);
CREATE INDEX IF NOT EXISTS idx_loans_next_due ON loans(next_due_date);

CREATE TABLE IF NOT EXISTS loan_repayments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    loan_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    principal_portion DECIMAL(15,2) DEFAULT 0,
    interest_portion DECIMAL(15,2) DEFAULT 0,
    late_fee_portion DECIMAL(15,2) DEFAULT 0,
    balance_after DECIMAL(15,2) NOT NULL,
    due_date DATETIME,
    paid_at DATETIME,
    status TEXT DEFAULT 'pending',
    reference TEXT,
    notes TEXT,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_loan_repay_loan ON loan_repayments(loan_id);

CREATE TABLE IF NOT EXISTS loan_guarantors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    loan_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    amount_guaranteed DECIMAL(15,2) NOT NULL,
    status TEXT DEFAULT 'pending',
    accepted_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS loan_collaterals (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    loan_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    description TEXT,
    estimated_value DECIMAL(15,2) NOT NULL,
    document_path TEXT,
    status TEXT DEFAULT 'pending',
    created_at DATETIME,
    updated_at DATETIME
);

CREATE TABLE IF NOT EXISTS loan_credit_scores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER UNIQUE NOT NULL,
    score INTEGER DEFAULT 500,
    tier TEXT DEFAULT 'Silver',
    total_borrowed DECIMAL(15,2) DEFAULT 0,
    total_repaid DECIMAL(15,2) DEFAULT 0,
    on_time_payments INTEGER DEFAULT 0,
    late_payments INTEGER DEFAULT 0,
    defaults INTEGER DEFAULT 0,
    last_calculated_at DATETIME,
    created_at DATETIME,
    updated_at DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

PRAGMA foreign_keys = ON;

SQL

echo -e "${GREEN}✅ All tables provisioned.${NC}"

# ============================================================
# 11. VERIFY TABLE COUNT
# ============================================================
TOTAL=$(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM sqlite_master WHERE type='table';")
echo -e "${BLUE}📊 Total tables created: $TOTAL${NC}"

# ============================================================
# 12. RUN LARAVEL MIGRATIONS (for any remaining tables)
# ============================================================
echo -e "${YELLOW}🗃️  Running remaining migrations...${NC}"
php artisan migrate --force || echo -e "${YELLOW}⚠️  Some migrations may have been skipped.${NC}"

# ============================================================
# 13. RUN POST-AUTOLOAD SCRIPTS
# ============================================================
echo -e "${YELLOW}🔄 Running post-autoload scripts...${NC}"
composer run-script post-autoload-dump 2>/dev/null || true

# ============================================================
# 14. SEED CORE DATA
# ============================================================
echo -e "${YELLOW}🌱 Seeding core data...${NC}"

# --- 14.1 Admin user ---
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
        'referral_code' => 'ADMIN888',
    ]);
    echo '✅ Admin user created.' . PHP_EOL;
}
" || true

# --- 14.2 Auto-verify on registration ---
PHP_FILE="app/Http/Controllers/Auth/RegisteredUserController.php"
if [ -f "$PHP_FILE" ] && ! grep -q "email_verified_at = now()" "$PHP_FILE"; then
    sed -i '/\$user = User::create/,/event(new Registered(\$user))/ {
        s/event(new Registered(\$user));/    \$user->email_verified_at = now();\n    \$user->save();\n\n    event(new Registered(\$user));/
    }' "$PHP_FILE"
    echo -e "${GREEN}   ✅ Auto-verify patched.${NC}"
fi

# --- 14.3 Loan products ---
[ -f "database/seeders/LoanProductSeeder.php" ] && \
    php artisan db:seed --class=LoanProductSeeder --force 2>/dev/null || true

# --- 14.4 Investment plans ---
[ -f "database/seeders/InvestmentPlanSeeder.php" ] && \
    php artisan db:seed --class=InvestmentPlanSeeder --force 2>/dev/null || true

# --- 14.5 RX Machines ---
[ -f "database/seeders/MachinesSeeder.php" ] && \
    php artisan db:seed --class=MachinesSeeder --force 2>/dev/null || true

# --- 14.6 Trading framework ---
[ -f "database/seeders/TradingSeeder.php" ] && \
    php artisan db:seed --class=TradingSeeder --force 2>/dev/null || true

# --- 14.7 Lottery ---
php artisan tinker --execute="
\App\Models\LotteryGame::unguard();
if (\App\Models\LotteryGame::count() === 0) {
    \App\Models\LotteryGame::create([
        'name' => 'Divine Cosmic Slots',
        'description' => '8888 Hz frequency slot machine',
        'min_bet' => 10, 'max_bet' => 1000, 'ticket_price' => 10,
        'is_active' => 1, 'settings' => '{}',
        'progressive_jackpot' => 1000, 'jackpot_contribution_rate' => 5,
        'base_rtp' => 95, 'vip_rtp' => 97, 'promo_rtp' => 99,
        'volatility' => 'medium',
        'enable_free_spins' => 1, 'enable_bonus_buy' => 1, 'bonus_buy_price' => 100,
    ]);
    echo '✅ Lottery game created.' . PHP_EOL;
}
" || true

# --- 14.8 Credit scores backfill ---
php artisan tinker --execute="
\App\Models\User::all()->each(function (\$u) {
    \App\Models\LoanCreditScore::firstOrCreate(
        ['user_id' => \$u->id],
        ['score' => 500, 'tier' => 'Silver']
    );
    \App\Models\Wallet::firstOrCreate(
        ['user_id' => \$u->id],
        ['balance' => 0, 'currency' => 'KES']
    );
    \App\Models\TradingAccount::firstOrCreate(
        ['user_id' => \$u->id],
        ['balance' => 0, 'locked_balance' => 0, 'btc_balance' => 0]
    );
    \App\Models\TradingProfile::firstOrCreate(
        ['user_id' => \$u->id],
        ['username' => 'trader_' . \$u->id, 'is_public' => 1, 'allow_copy_trading' => 1, 'copy_ratio' => 1]
    );
});
echo '✅ Wallets, trading accounts, profiles, credit scores initialised.' . PHP_EOL;
" || true

# ============================================================
# 15. SET PERMISSIONS
# ============================================================
echo -e "${YELLOW}🔧 Setting permissions...${NC}"
mkdir -p storage/framework/{sessions,views,cache}
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

# ============================================================
# 16. CLEAR & OPTIMIZE CACHES
# ============================================================
echo -e "${YELLOW}🧹 Clearing caches...${NC}"
php artisan config:clear  2>/dev/null || true
php artisan cache:clear   2>/dev/null || true
php artisan view:clear    2>/dev/null || true
php artisan route:clear   2>/dev/null || true

# ============================================================
# 17. FINAL SUMMARY
# ============================================================
echo ""
echo "═══════════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ Racksephnox — FULL DATABASE PROVISIONING COMPLETE${NC}"
echo "═══════════════════════════════════════════════════════════════"
echo -e "${BLUE}📂 Project root:   $PROJECT_ROOT${NC}"
echo -e "${BLUE}🗄️  Database path:  $DB_PATH${NC}"
echo -e "${BLUE}🌍 Environment:    $ENV${NC}"
echo -e "${BLUE}📊 Total tables:   $(sqlite3 "$DB_PATH" "SELECT COUNT(*) FROM sqlite_master WHERE type='table';")${NC}"
echo ""
echo -e "${MAGENTA}📦 DOMAINS PROVISIONED:${NC}"
echo "   ⚡ Core              (users, wallets, transactions, sessions, cache, jobs...)"
echo "   🏦 Banking           (deposits, withdrawals, mpesa, bank accounts, kyc)"
echo "   📈 Investments       (plans, investments)"
echo "   🤖 Machines          (machines, vips, machine_investments)"
echo "   💹 Trading           (pairs, orders, candles, profiles, bonus trackers...)"
echo "   🎰 Lottery           (games, spins, tournaments, guilds, missions...)"
echo "   💸 Loans             (products, loans, repayments, guarantors, collaterals...)"
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
echo -e "${MAGENTA}Φ = 1.61803398875  ·  λ = 1.27201964951  ·  888 Hz${NC}"
echo "═══════════════════════════════════════════════════════════════"
MASTERSCRIPT

chmod +x /root/racksephnox/db-provision.sh
