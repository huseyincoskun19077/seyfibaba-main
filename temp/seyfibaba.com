server {
    listen 80;
    server_name seyfibaba.com www.seyfibaba.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name seyfibaba.com www.seyfibaba.com;

    ssl_certificate /etc/letsencrypt/live/seyfibaba.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seyfibaba.com/privkey.pem;

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /uploads/ {
        alias /var/www/seyfibaba/backend/public/uploads/;
    }
}