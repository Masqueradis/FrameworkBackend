### Directory Structure

* **`app/`** - The core of the application logic.
    * **`Http/Controllers/`** - Thin controllers responsible for handling incoming HTTP requests, delegating tasks to services, and returning JSON responses or Blade views. Divided into `Api\` and `Admin\` namespaces.
    * **`Services/`** - Contains the core business logic (e.g., `ProductService`, `CategoryService`, `AuthService`).
    * **`Data/`** - Data Transfer Objects (DTOs) powered by `spatie/laravel-data`. Used for strict validation and typed transfer of incoming request data (e.g., `ProductSaveData`, `RegisterUserData`).
    * **`Repositories/`** - Handles direct database queries and abstracts Eloquent logic away from the services.
    * **`Models/`** - Eloquent ORM models representing database tables (`Product`, `Category`, `User`, etc.).
    * **`Http/Resources/`** - API Resources for transforming models into standardized, front-end-friendly JSON structures.
* **`database/`** - Database schema and population.
    * **`migrations/`** - Version control for the database schema.
    * **`seeders/`** & **`factories/`** - Tools for populating the database with default admin accounts and fake testing data.
* **`resources/`** - Frontend assets and views.
    * **`views/`** - Blade HTML templates for the Web UI (Admin dashboard, Auth forms, Catalog).
    * **`js/` & `css/`** - Source files compiled by Vite.
* **`routes/`** - Route definitions.
    * **`api.php`** - Stateless REST API endpoints (returns JSON, documented via Swagger).
    * **`web.php`** - Stateful browser routes (Blade views, authentication, admin panel).
* **`tests/`** - Automated testing suite (PHPUnit).
    * **`Feature/`** - API and Web integration tests ensuring high code coverage.
    * **`Unit/`** - Isolated logic tests.
* **`public/`** - The web server's document root. Contains the `index.php` entry point, compiled Vite assets (`build/`), and the `storage/` symlink for uploaded files.
* **`storage/`** - Generated files, including application logs (`logs/laravel.log`), compiled Blade templates, and user-uploaded files.
