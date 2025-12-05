# NodeJS Pong (WebSocket Server)

Node.js (Socket.IO) と Nginx を使用した、リアルタイム・マルチプレイヤー Pong ゲームです。
サーバーサイドでゲームロジックを判定し、Nginx をリバースプロキシとして使用して配信する構成です。

## 構成概要

- **Server Side**: `~/pong-server` (Node.js + Socket.IO)
- **Client Side**: `/var/www/html/pong` (index.html)
- **Middleware**: Nginx (Web Server / Reverse Proxy)

## 1. 事前準備 (Installation)

Ubuntu/Debian 系での環境構築手順です。

### 必要なソフトウェアのインストール

```bash
sudo apt update
sudo apt install nginx -y
sudo apt install avahi-daemon -y  # .local ドメインでのアクセス用

# Node.js のインストール (NodeSource スクリプト推奨)
curl -fsSL [https://deb.nodesource.com/setup_lts.x](https://deb.nodesource.com/setup_lts.x) | sudo -E bash -
sudo apt-get install -y nodejs
````

## 2\. セットアップ手順

### 2-1. サーバー側 (Node.js) の準備

ホームディレクトリ配下にプロジェクトを作成します。

```bash
mkdir ~/pong-server
cd ~/pong-server
npm init -y
npm install socket.io
```

※ `server.js` をこのディレクトリに配置してください。

### 2-2. クライアント側 (Web) の準備

Nginx のドキュメントルート配下にクライアント用ディレクトリを作成します。

```bash
sudo mkdir -p /var/www/html/pong
```

※ `index.html` を `/var/www/html/pong/index.html` として保存してください。

## 3\. サーバー設定 (Configuration)

### Nginx 設定 (リバースプロキシ)

`/etc/nginx/sites-available/default` を編集し、静的ファイルへのアクセスと WebSocket 通信（ポート3000）への転送を設定します。

\<details\>
\<summary\>設定ファイルの中身を表示\</summary\>

```nginx
server {
    listen 80;
    server_name ubuntu.local; # ホスト名に合わせて変更してください

    # ルートアクセスを /pong/ にリダイレクト
    location = / {
        return 301 /pong/;
    }

    # HTML/CSS/JS などの静的ファイルを配信
    location /pong/ {
        alias /var/www/html/pong/;
        index index.html;
    }

    # WebSocket通信を Node.js (3000番) に転送
    location /socket.io/ {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

\</details\>

設定の反映:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## 4\. 実行方法

### 手動起動

```bash
cd ~/pong-server
node server.js
```

ブラウザで `http://ubuntu.local/pong/`（または設定したホスト名）に2つのタブでアクセスするとゲームが開始されます。

## 5\. 自動起動設定 (Systemd)

サーバー再起動時にも自動的に Node.js が起動するように設定します。

**ファイル作成**: `/etc/systemd/system/pong.service`

\<details\>
\<summary\>pong.service の内容を表示\</summary\>

```ini
[Unit]
Description=Pong WebSocket Server (Node.js)
After=network.target

[Service]
Type=simple
Restart=always

# 実行ユーザー (環境に合わせて変更)
User=ubuntu

# server.js があるディレクトリ
WorkingDirectory=/home/ubuntu/pong-server

# 実行コマンド ('which node' で調べたパスを使用)
ExecStart=/usr/bin/node server.js

[Install]
WantedBy=multi-user.target
```

\</details\>

**サービスの有効化と起動**:

```bash
sudo systemctl daemon-reload
sudo systemctl enable pong.service
sudo systemctl start pong.service
```

## License

MIT