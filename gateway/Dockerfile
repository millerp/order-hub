# Gateway Nginx + Node for runtime compilation
FROM nginx:alpine

# Install Node.js and NPM
RUN apk add --no-cache nodejs npm shadow

ARG USER_ID=1000
ARG GROUP_ID=1000

# Create a node user if it doesn't exist (it usually comes with the node apk on some distros, but not alpine)
RUN groupadd -g ${GROUP_ID} node || groupmod -g ${GROUP_ID} node || true && \
    useradd -u ${USER_ID} -m -g ${GROUP_ID} node || usermod -u ${USER_ID} -g ${GROUP_ID} node || true

WORKDIR /app

# Copy Nginx config
COPY gateway/nginx.conf /etc/nginx/nginx.conf

# Copy entrypoint script
COPY gateway/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# The frontend source will be mounted as a volume in docker-compose
# But we create the directory to be safe
RUN mkdir -p /app/frontend && chown -R node:node /app

# Allow node user to write to nginx html and run nginx
RUN mkdir -p /var/cache/nginx/client_temp /var/cache/nginx/proxy_temp /var/cache/nginx/fastcgi_temp /var/cache/nginx/uwsgi_temp /var/cache/nginx/scgi_temp && \
    chown -R node:node /usr/share/nginx/html /var/cache/nginx /var/log/nginx /etc/nginx/conf.d && \
    touch /var/run/nginx.pid && \
    chown node:node /var/run/nginx.pid

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
