### Directory info
- **`nginx/`** – contains default configuration for nginx and a Dockerfile to copy this config
- **`php/`** – contains Xdebug configuration and a Dockerfile with settings necessary for the application

### Files info
- **`.env.example`** - example of .env, used to create .env using Makefile (init target)
- **`compose.yaml`** - yaml file for docker compose, contains several services like: php, PostgreSQL, nginx and mailhog.