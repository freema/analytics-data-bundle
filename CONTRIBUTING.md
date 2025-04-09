# Contributing Guidelines

Thank you for your interest in contributing to the GA4 Analytics Data Bundle. Below are some guidelines to help you get started.

## Setting Up the Development Environment

1. Clone the repository:
   ```bash
   git clone https://github.com/freema/ga4-analytics-data-bundle.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Start the development server using Docker:
   ```bash
   docker-compose up
   ```

## Development Tools

### Running Tests

To run tests, use the following commands:

```bash
# Run PHPUnit tests
composer test

# Run code style checks
composer cs

# Run static analysis
composer phpstan

# Fix code style issues
composer cs-fix
```

Or using Taskfile:

```bash
task tests
task cs
task phpstan
task cs-fix
```

### Testing with Different Symfony Versions

The bundle supports Symfony 5.4, 6.4, and 7.1. You can test compatibility with these versions:

```bash
task symfony-54
task symfony-64
task symfony-71
```

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