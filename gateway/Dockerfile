# Stage 1: Build Vue.js SPA
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY frontend/package*.json ./
RUN npm install
COPY frontend .
RUN npm run build

# Stage 2: Nginx Gateway + SPA
FROM nginx:alpine
COPY gateway/nginx.conf /etc/nginx/nginx.conf
COPY --from=frontend-builder /app/dist /usr/share/nginx/html
