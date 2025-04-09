# GA4 Analytics Data Bundle Recipe

This directory contains the [Symfony Flex](https://symfony.com/doc/current/setup/flex.html) recipe for automatically configuring the GA4 Analytics Data Bundle when it's installed in a Symfony project.

## What This Recipe Does

When the `freema/ga4-analytics-data-bundle` package is installed in a Symfony Flex project, this recipe will:

1. Register the bundle in your `config/bundles.php` file
2. Create the `config/packages/ga4_analytics_data.yaml` configuration file with default settings
3. Add the necessary environment variables to your `.env` file
4. Add the credentials file path to your `.gitignore` to prevent accidental commits

## After Installation

After installing the bundle, you'll need to:

1. Create or obtain a Google Service Account credentials JSON file
2. Place the credentials file in your project (default location: `config/analytics-credentials.json`)
3. Set your Analytics Property ID in the `.env` file or `.env.local` file

## Documentation

For full documentation on how to use the bundle, please see the main README file in the repository root.