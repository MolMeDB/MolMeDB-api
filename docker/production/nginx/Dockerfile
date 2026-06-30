# syntax=docker/dockerfile:1

FROM nginx:alpine AS production

# Copy custom Nginx configuration
# -----------------------------------------------------------
# Replace the default Nginx configuration with our custom one
# that is optimized for serving a Laravel application.
# -----------------------------------------------------------
COPY ./docker/production/nginx/nginx.conf /etc/nginx/nginx.conf

# Nginx only needs Laravel's versioned public files. Application source and PHP
# dependencies remain in the backend image.
COPY public /var/www/public

# Set the working directory to the public folder
WORKDIR /var/www/public

# Expose the port used by the production Compose stack and start Nginx.
EXPOSE 443
CMD ["nginx", "-g", "daemon off;"]
