# Use an official PHP runtime as the base image
FROM php:8.4-apache

# Set the working directory
WORKDIR /var/www/html

# Copy all project files to the container
COPY . /var/www/html/

# Expose port 8080 (Render's default port)
EXPOSE 8080

# Start Apache server
CMD ["apache2-foreground"]
