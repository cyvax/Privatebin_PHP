# Contributing

Contributions are welcome via pull requests or issues. Documentation improvements on the wiki are equally appreciated.

## Reporting issues

- Search existing issues before opening a new one
- Include PHP version, extension availability (`ext-gmp`, `ext-zlib`, `ext-fileinfo`), and a minimal reproducible example

## Pull requests

1. Fork the repository and create a branch from `main`
2. Make your changes
3. Run the test suite and make sure everything passes
4. Open a pull request against `main` with a clear description of what changed and why

## Running tests

The test suite requires PHP 8.5 and PHPUnit.

> [!TIP]
> PHP 8.5 is required because `tests/Unit/PrivatebinPipeTest.php` uses the native `|>` pipe operator directly to validate `PrivatebinPipe`.

```bash
composer install
./vendor/bin/phpunit
```

Unit tests live in `tests/Unit/` and cover all three clients (`Privatebin`, `PrivatebinSnakeCase`, `PrivatebinPipe`) as well as `Base58` encoding. 
They run fully offline with no external dependencies.

End-to-end tests live in `tests/E2E/` and make real HTTP requests to a running PrivateBin instance.

They cover the full paste lifecycle across all clients, including attachments, compression modes, formatters, passwords, and burn/discussion flags. 

The target URL is hardcoded to `http://localhost/PrivateBin/`, you will need a local PrivateBin instance running before running them.

E2E tests are tagged with the `e2e`.

```bash
# Unit tests only
./vendor/bin/phpunit --exclude-group e2e

# E2E tests only (requires a running PrivateBin instance)
./vendor/bin/phpunit --group e2e
```

## Guidelines

- Keep changes focused, one concern per PR
- Follow the existing code style
- If you add a new feature, update the wiki documentation accordingly
- Interfaces in `Cyvax\Contracts` are considered stable extension points, changes to them are breaking changes
