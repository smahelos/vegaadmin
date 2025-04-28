FROM nginx:1.25-alpine

# Kopírování konfigurace Nginx
COPY ./docker/nginx/conf.d/default.conf /etc/nginx/conf.d/default.conf

# Expose port 80
EXPOSE 80

# Start Nginx
CMD ["nginx", "-g", "daemon off;"]