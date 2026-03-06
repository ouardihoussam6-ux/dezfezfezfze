#!/bin/bash
# Smart Park — Script de déploiement
# Lance depuis le dossier du projet sur le Raspberry Pi :
#   bash deploy.sh

set -e

WEB_ROOT="/var/www/html"
PROJECT="$(cd "$(dirname "$0")" && pwd)"
LOCAL_IP=$(hostname -I | awk '{print $1}')

echo "===================================================="
echo " Smart Park — Deploiement"
echo "===================================================="

# --- Verifications ---
echo "[1/7] Verification des dependances..."

if ! command -v apache2 &>/dev/null; then
    echo "     Apache non installe. Installation..."
    sudo apt-get update -qq
    sudo apt-get install -y apache2
fi

if ! command -v php &>/dev/null; then
    echo "     PHP non installe. Installation..."
    sudo apt-get install -y php php-mysql libapache2-mod-php
fi

if ! command -v mysql &>/dev/null; then
    echo "     MariaDB non installe. Installation..."
    sudo apt-get install -y mariadb-server
fi

echo "     OK"

# --- Base de données (via sudo mysql, bypass unix_socket auth) ---
echo "[2/7] Initialisation de la base de donnees..."

sudo mysql << 'SQL'
CREATE DATABASE IF NOT EXISTS smart_park
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'admin'@'localhost' IDENTIFIED BY 'admin';
ALTER USER 'admin'@'localhost' IDENTIFIED BY 'admin';
GRANT ALL PRIVILEGES ON smart_park.* TO 'admin'@'localhost';
FLUSH PRIVILEGES;

USE smart_park;

CREATE TABLE IF NOT EXISTS badges (
    id         INT          NOT NULL AUTO_INCREMENT,
    tag_uid    VARCHAR(50)  NOT NULL,
    nom        VARCHAR(100) NOT NULL DEFAULT 'Inconnu',
    autorise   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_uid (tag_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS places (
    id_place   INT         NOT NULL,
    etat       ENUM('libre','occupee','panne') NOT NULL DEFAULT 'libre',
    uid_actuel VARCHAR(50) DEFAULT NULL,
    updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_place)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
    id         INT         NOT NULL AUTO_INCREMENT,
    date_heure TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    tag_id     VARCHAR(50) NOT NULL,
    action     VARCHAR(50) NOT NULL,
    slot       INT         NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_tag  (tag_id),
    KEY idx_date (date_heure)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO places (id_place, etat) VALUES (1,'libre'),(2,'libre'),(3,'libre');
SQL

echo "     OK"

# --- Sauvegarde des anciens fichiers ---
echo "[3/7] Sauvegarde des fichiers existants (suffixe _old)..."

backup() {
    local name="$1"
    local target="$WEB_ROOT/$name"
    local ext="${name##*.}"
    local bak

    # Dossier ou fichier sans extension -> name_old
    # Fichier avec extension         -> name_old.ext
    if [ "$name" = "$ext" ]; then
        bak="$WEB_ROOT/${name}_old"
    else
        bak="$WEB_ROOT/${name%.*}_old.${ext}"
    fi

    if [ -e "$target" ]; then
        sudo rm -rf "$bak"
        sudo mv "$target" "$bak"
        echo "     $name  =>  ${bak##*/}"
    fi
}

# Dossiers
backup "api"
backup "assets"
backup "config"
backup "includes"
backup "models"

# Fichiers PHP
backup "index.php"
backup "inscription.php"
backup "badges.php"
backup "logs.php"
backup "setup.php"

# Fichier de reset (on ne sauvegarde pas, on garde l'existant s'il y en a un)
if [ ! -f "$WEB_ROOT/reset_ordre.txt" ]; then
    sudo cp "$PROJECT/reset_ordre.txt" "$WEB_ROOT/"
fi

echo "     OK"

# --- Copie des nouveaux fichiers ---
echo "[4/7] Copie des nouveaux fichiers vers $WEB_ROOT..."

sudo cp -r "$PROJECT/api"      "$WEB_ROOT/"
sudo cp -r "$PROJECT/assets"   "$WEB_ROOT/"
sudo cp -r "$PROJECT/config"   "$WEB_ROOT/"
sudo cp -r "$PROJECT/includes" "$WEB_ROOT/"
sudo cp -r "$PROJECT/models"   "$WEB_ROOT/"

sudo cp "$PROJECT/index.php"       "$WEB_ROOT/"
sudo cp "$PROJECT/inscription.php" "$WEB_ROOT/"
sudo cp "$PROJECT/badges.php"      "$WEB_ROOT/"
sudo cp "$PROJECT/logs.php"        "$WEB_ROOT/"
sudo cp "$PROJECT/setup.php"       "$WEB_ROOT/"

echo "     OK"

# --- Permissions ---
echo "[5/7] Application des permissions..."

sudo chown -R www-data:www-data "$WEB_ROOT"
sudo find "$WEB_ROOT" -type d -exec chmod 755 {} \;
sudo find "$WEB_ROOT" -type f -exec chmod 644 {} \;
sudo chmod 664 "$WEB_ROOT/reset_ordre.txt"

echo "     OK"

# --- Apache ---
echo "[6/7] Redemarrage d'Apache..."
sudo systemctl restart apache2
echo "     OK"

# --- Test ---
echo "[7/7] Test de connectivite..."
sleep 1
HTTP=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/index.php 2>/dev/null || echo "000")
if [ "$HTTP" = "200" ]; then
    echo "     HTTP $HTTP — Site operationnel"
else
    echo "     HTTP $HTTP — Verifiez Apache et PHP"
fi

echo ""
echo "===================================================="
echo " Termine !"
echo " Dashboard : http://$LOCAL_IP/index.php"
echo " API ESP32 : http://$LOCAL_IP/api/check_uid.php"
echo ""
echo " Anciens fichiers gardes avec le suffixe _old :"
echo "   api_old/  assets_old/  config_old/  models_old/"
echo "   index_old.php  badges_old.php  logs_old.php  etc."
echo "===================================================="
