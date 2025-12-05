# Multiplayer Pong with Socket.IO

Node.js + Socket.IO + Nginx で構築された、リアルタイム対戦型Pongゲーム。
Websocketを使用した低遅延な同期通信と、Nginxのリバースプロキシ構成を含めた実装です。

## Features

- **Real-time Gameplay**: Socket.IOによる双方向通信
- **Room Management**: 接続ユーザーの自動マッチングとルーム生成
- **Server-side Logic**: 座標計算や判定をサーバー側で完結させる堅牢な設計
- **Production Ready**: Nginxリバースプロキシ + Systemdによるサービス化

## Tech Stack

- **Runtime**: Node.js (v18+)
- **Protocol**: WebSocket (Socket.IO v4)
- **Web Server**: Nginx
- **OS**: Ubuntu / Debian Linux

## Installation

### 1. Setup Node.js Application

```bash
# Clone repository
git clone [https://github.com/YOUR_USERNAME/pong-server.git](https://github.com/YOUR_USERNAME/pong-server.git)
cd pong-server

# Install dependencies
npm install
````

### 2\. Configuration

`server.js` 内の `allowedOrigins` を環境に合わせて変更してください。

```javascript
const allowedOrigins = [
  "[http://your-hostname.local](http://your-hostname.local)",  // Nginxのserver_name
  "http://localhost:3000"
];
```

## Usage (Development)

ローカル環境での動作確認コマンドです。

```bash
npm start
# Server running at http://localhost:3000
```

## Deployment

本番環境（Linuxサーバー）へのデプロイ設定例です。

### Systemd Service

常時稼働させるためのSystemd設定です。

\<details\>
\<summary\>View pong.service\</summary\>

`/etc/systemd/system/pong.service`

```ini
[Unit]
Description=Pong WebSocket Server
After=network.target

[Service]
Type=simple
User=ubuntu
WorkingDirectory=/home/ubuntu/pong-server
ExecStart=/usr/bin/node server.js
Restart=always

[Install]
WantedBy=multi-user.target
```

\</details\>

```bash
sudo systemctl enable pong
sudo systemctl start pong
```

### Nginx Configuration

WebSocketを通すためのリバースプロキシ設定です。

\<details\>
\<summary\>View Nginx Config\</summary\>

`/etc/nginx/sites-available/default`

```nginx
server {
    listen 80;
    server_name your-hostname.local;

    location = / {
        return 301 /pong/;
    }

    location /pong/ {
        alias /var/www/html/pong/;
        index index.html;
    }

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

```bash
sudo nginx -t
sudo systemctl reload nginx
```

## License

[MIT](https://www.google.com/search?q=LICENSE)

```