module.exports = {
  apps: [
    {
      name: 'sey-frontend',
      script: 'server.js',
      cwd: '/opt/seyfibaba-main/frontend',
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '1G',
      env: {
        NODE_ENV: 'production',
        PORT: 3001,
        BACKEND_PORT: 8000,
        BACKEND_PROXY_TARGET: 'http://127.0.0.1',
        BACKEND_PROXY_HOST: 'admin.seyfibaba.com',
      },
    },
  ],
};
