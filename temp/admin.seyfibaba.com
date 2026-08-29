server {
    listen 80;
    server_name admin.seyfibaba.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    server_name admin.seyfibaba.com;

    ssl_certificate /etc/letsencrypt/live/seyfibaba.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/seyfibaba.com/privkey.pem;

    root /var/www/seyfibaba/backend/public;
    index index.php index.html;

    client_max_body_size 50M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}