# Contributing Guidelines

Thank you for your interest in contributing to the GA4 Analytics Data Bundle. Below are some guidelines to help you get started.

## Setting Up the Development Environment

1. Clone the repository:
   ```bash
   git clone https://github.com/freema/ga4-analytics-data-bundle.git
   cd ga4-analytics-data-bundle
   ```

2. Set up your Google Analytics credentials:
   
   a. Copy the sample credentials file and create your `.env` file if not already present:
      ```bash
      cp -n dev/.env.example dev/.env
      ```

   b. Place your Google Analytics service account JSON key file in:
      ```
      dev/ssh/credentials.json
      ```
      > ⚠️ **IMPORTANT:** Never commit your actual credentials to git! The `dev/ssh/` directory 
      > (except for the sample file) and `dev/.env` are gitignored for security reasons.

   c. Update your `dev/.env` file with your GA4 property ID and the path to your credentials file:
      ```
      ANALYTICS_PROPERTY_ID=your-property-id
      ANALYTICS_CREDENTIALS_PATH=/app/dev/ssh/credentials.json
      ```
      Note that the path is inside the Docker container, so it should always start with `/app/`.

3. Start the development environment using Docker:
   ```bash
   docker-compose up -d
   ```
   
   This will set up the development environment and automatically install dependencies on the first run.

4. Once Docker is running, you can access the demo application at:
   ```
   http://localhost:8080/
   ```
   
   The demo provides a simple interface to test the bundle's functionality.

5. Run commands inside the Docker container:
   ```bash
   # Example: Running tests inside the container
   docker-compose exec php composer test
   
   # Example: Running code style checks
   docker-compose exec php composer cs
   ```

## Development Tools

All development commands should be run inside the Docker container to ensure a consistent environment.

### Running Tests

```bash
# Run PHPUnit tests
docker-compose exec php composer test

# Run code style checks
docker-compose exec php composer cs

# Run static analysis
docker-compose exec php composer phpstan

# Fix code style issues
docker-compose exec php composer cs-fix
```

Or using Taskfile:

```bash
docker-compose exec php vendor/bin/task tests
docker-compose exec php vendor/bin/task cs
docker-compose exec php vendor/bin/task phpstan
docker-compose exec php vendor/bin/task cs-fix
```

### Testing with Different Symfony Versions

The bundle supports Symfony 5.4, 6.4, and 7.1. You can test compatibility with these versions:

```bash
docker-compose exec php vendor/bin/task symfony-54
docker-compose exec php vendor/bin/task symfony-64
docker-compose exec php vendor/bin/task symfony-71
```

## Development Environment Structure

The `/dev` directory contains a simple Symfony application for development and testing purposes:

- `/dev/Controller`: Demo controllers to showcase bundle features
- `/dev/config`: Configuration for the development environment
- `/dev/templates`: Demo templates (if any)

When you access `http://localhost:8080/`, you're interacting with this development application.

## Code Style

We follow PSR-12 coding standards and Symfony best practices. All code should be properly formatted and include appropriate docblocks.

## Project Structure

- `/src`: Source code for the bundle
  - `/Analytics`: Core analytics classes
  - `/Cache`: Caching functionality
  - `/Client`: Client registry for managing multiple GA properties
  - `/DataCollector`: Symfony profiler integration
  - `/DependencyInjection`: Bundle configuration
  - `/Domain`: Domain models and value objects
  - `/Exception`: Custom exceptions
  - `/Http`: HTTP client functionality
  - `/Processor`: Data processing classes
  - `/Response`: Response models
  - `/Resources`: Configuration and templates
- `/tests`: Unit tests
- `/dev`: Development environment for testing the bundle
- `/test`: Symfony version compatibility tests

## Pull Request Process

1. Fork the repository and create your branch from `main`.
2. Make sure your code follows the style guidelines.
3. Add or update tests as necessary.
4. Update documentation as needed.
5. Submit a pull request.

## License

By contributing, you agree that your contributions will be licensed under the project's MIT License.