# Store App

### Description
Store App is a web-based e-commerce platform for computer components built with Laravel. It provides a comprehensive catalog of PC parts (processors, graphics cards, memory, storage, etc.) with advanced filtering and search capabilities. Users can browse products, manage a shopping cart, place orders, and track order history. The application includes user authentication, admin panel for inventory management, and order processing. It is containerized using Docker for easy deployment and development, uses PostgreSQL as the database, and integrates Mailhog for email testing during development.

### Tech Spec
- **Backend Framework**: Laravel (PHP) – utilizes Eloquent ORM, Blade templating, and built-in authentication.
- **Frontend**: Blade templates with Bootstrap for responsive design; optional JavaScript for enhanced interactivity.
- **Database**: PostgreSQL – relational database for storing products, users, orders, and inventory data.
- **Containerization**: Docker – services orchestrated via docker-compose (Laravel application, PostgreSQL, Mailhog, Nginx).
- **Mail Testing**: Mailhog – captures and displays emails sent by the application in a local web interface.
- **Environment**: Runs in Docker containers, ensuring consistent development and production environments.
- **Additional Tools**: Composer for PHP dependency management, NPM for frontend assets, and Laravel Mix for asset compilation.

### How to use
1. Run `git clone repo`
2. Run `make setup`
3. Done!

Now you can access the website at http://localhost

### Directory info
#### Docker 
- **nginx** – contains default configuration for nginx and a Dockerfile to copy this config
- **php** – contains Xdebug configuration and a Dockerfile with settings necessary for the application
