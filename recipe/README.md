# Symfony Flex recipe

This directory holds the [Symfony Flex](https://symfony.com/doc/current/setup/flex.html)
recipe for `freema/ga4-analytics-data-bundle`, as it would be submitted to
[symfony/recipes-contrib](https://github.com/symfony/recipes-contrib) under
`freema/ga4-analytics-data-bundle/1.0/`. It is not published yet, so Flex does
not apply it; until it is, follow the manual setup in the main README.

Once published, `composer require freema/ga4-analytics-data-bundle` will:

1. Register the bundle in `config/bundles.php`
2. Create `config/packages/ga4_analytics_data.yaml` with one `default` client
3. Add `ANALYTICS_PROPERTY_ID` and `ANALYTICS_CREDENTIALS_PATH` to `.env`
4. Add `config/analytics-credentials.json` to `.gitignore`

`ANALYTICS_CREDENTIALS_PATH` defaults to
`%kernel.project_dir%/config/analytics-credentials.json`; the config reads it
with the `resolve:` env var processor, which replaces `%kernel.project_dir%`.
