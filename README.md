# Gomoku (PHP + MySQL)

PHPとMySQLで実装されたステートレスな五目並べアプリケーション。
HTTPポーリングを使用した非同期通信により、ブラウザ間でのリアルタイム対戦を実現しています。

## Requirements

* **PHP**: 8.0+ (pdo_mysql extension enabled)
* **MySQL**: 5.7+ or 8.0+
* **Web Server**: Apache or Nginx

## Installation

### 1. Setup Database
MySQLにてデータベースおよびテーブルを作成します。

```sql
CREATE DATABASE gomoku_db;
CREATE USER 'gomoku_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON gomoku_db.* TO 'gomoku_user'@'localhost';
FLUSH PRIVILEGES;

USE gomoku_db;

CREATE TABLE games (
    game_id INT AUTO_INCREMENT PRIMARY KEY,
    player_1_id VARCHAR(255),
    player_2_id VARCHAR(255),
    current_turn_id VARCHAR(255),
    status VARCHAR(20) NOT NULL DEFAULT 'waiting',
    winner_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE moves (
    move_id INT AUTO_INCREMENT PRIMARY KEY,
    game_id INT NOT NULL,
    player_id VARCHAR(255) NOT NULL,
    x_coord TINYINT NOT NULL,
    y_coord TINYINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (game_id) REFERENCES games(game_id)
);
````

### 2\. Deploy Application

Webサーバーのドキュメントルート（例: `/var/www/html/gomoku`）へソースコードを展開します。

```bash
# Example
git clone [https://github.com/your-repo/gomoku-php.git](https://github.com/your-repo/gomoku-php.git) /var/www/html/gomoku
sudo chown -R www-data:www-data /var/www/html/gomoku
```

### 3\. Configuration

`db_connect.php` の接続情報を環境に合わせて修正してください。

```php
// db_connect.php
$host = 'localhost';
$db   = 'gomoku_db';
$user = 'gomoku_user';
$pass = 'your_strong_password'; // set your password
```

## Web Server Config

### Nginx Example

PHP-FPMを使用する場合の `sites-available` 設定例です。

```nginx
server {
    listen 80;
    server_name your-domain.local;
    root /var/www/html/gomoku;
    index index.html;

    location / {
        try_files $uri $uri/ =404;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

## Maintenance (Optional)

放置されたゲームデータを削除するために、Cronの設定を推奨します。

```bash
# crontab -e
30 * * * * php /var/www/html/gomoku/cleanup_games.php
```

## License

MIT