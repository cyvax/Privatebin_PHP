![GitHub License](https://img.shields.io/github/license/cyvax/Privatebin_PHP?style=for-the-badge)
![version](https://img.shields.io/github/v/tag/cyvax/Privatebin_PHP?label=VERSION&style=for-the-badge)
![packagist](https://img.shields.io/packagist/dt/cyvax/privatebin_php?style=for-the-badge)
![Packagist PHP Version Support](https://img.shields.io/packagist/php-v/cyvax/privatebin_php?style=for-the-badge)
![Codacy grade](https://img.shields.io/codacy/grade/13bbc8be0d134180b8221f014af50e74?style=for-the-badge)

PHP client library for [PrivateBin](https://github.com/PrivateBin/PrivateBin/).

Supports:

* Modern camelCase API (`PrivatebinPHP`)
* Original snake_case API (`PrivatebinSnakeCase`)
* Optional pipe-style helpers (`PrivatebinPHPPipe`)
* Attachments
* Password-protected pastes
* Burn-after-reading
* Discussions
* Compression
* Custom formatters
* SSL verification control
* PHP 7.1+ compatibility

---

# Installation

```bash
composer require cyvax/privatebin_php
```

---

# Default Instance

By default, the library is configured to use:

```text
https://paste.i2pd.xyz/
```

You can override it with `setUrl()` or via constructor options.

---

# Usage

## Modern camelCase API

```php
use Cyvax\Clients\Privatebin;

$result = (new Privatebin())
    ->setUrl("https://privatebin.net/")
    ->setText("Because ignorance is bliss!")
    ->setExpire("1hour")
    ->encodeAndPost();
```

### Factory method

`PrivatebinPHP::init()` supports named parameters for a more concise setup on PHP 8+:

```php
use Cyvax\Clients\Privatebin;

$result = Privatebin::init(
    url: "https://privatebin.net/",
    text: "Because ignorance is bliss!",
    expire: "1hour",
)->encodeAndPost();
```

---

## Original snake_case API

```php
use Cyvax\Clients\PrivatebinSnakeCase;

$result = (new PrivatebinSnakeCase())
    ->set_url("https://privatebin.net/")
    ->set_text("Because ignorance is bliss!")
    ->set_expire("1hour")
    ->encode_and_post();
```

> [!NOTE]
> `PrivatebinSnakeCase` is also available as the alias `\Cyvax\PrivatebinPHP`
> for compatibility with older versions of the library.

---

## Constructor Options

Both clients accept an options array in their constructor:

```php
use Cyvax\Clients\Privatebin;

$result = (new Privatebin([
    "url"  => "https://privatebin.net/",
    "text" => "Because ignorance is bliss!",
]))->encodeAndPost();
```

---

## Attachments

```php
use Cyvax\Clients\Privatebin;

$result = (new Privatebin())
    ->setAttachment("/path/to/image.png")
    ->encodeAndPost();
```

> [!NOTE]
> `setAttachment()` throws `PrivatebinException` if the file cannot be read.

An optional second argument overrides the attachment filename:

```php
->setAttachment("/path/to/image.png", "screenshot.png")
```

---

## SSL Verification

SSL verification is enabled by default. Disable it only for local or self-signed instances:

```php
use Cyvax\Clients\Privatebin;

$result = (new Privatebin())
    ->setUrl("https://local.privatebin/")
    ->setSslVerify(false)
    ->setText("Hello!")
    ->encodeAndPost();
```

The `ssl_verify` key is also accepted in the constructor options array and in `PrivatebinPHP::init()`.

---

## Pipe-style API

`PrivatebinPHPPipe` provides functional-style helper closures.

On PHP versions supporting the pipe operator:

```php
use Cyvax\Clients\PrivatebinPipe;

$result = PrivatebinPipe::init("https://privatebin.net/")
    |> PrivatebinPipe::text("Hello!")
    |> PrivatebinPipe::expire("1hour")
    |> PrivatebinPipe::encodeAndPost();
```

Without pipe support, it is recommended to use `PrivatebinPHP` or `PrivatebinSnakeCase` directly.

If needed, closures can still be invoked manually:

```php
use Cyvax\Clients\PrivatebinPipe;

$client = PrivatebinPipe::init("https://privatebin.net/");

$client = PrivatebinPipe::text("Hello!")($client);
$client = PrivatebinPipe::expire("1hour")($client);

$result = PrivatebinPipe::encodeAndPost()($client);
```

---

## Fetching and Decrypting

```php
use Cyvax\Clients\Privatebin;

$client = new Privatebin();

// Fetch and decrypt in one call
$data = $client->fetchAndDecrypt($pasteId, $b58);

echo $data['paste'];
// $data['attachment'] and $data['attachment_name'] are set if the paste had an attachment
```

---

# Interfaces

| Interface                            | Namespace         | Used by                     |
|--------------------------------------|-------------------|-----------------------------|
| `PrivatebinClientInterface`          | `Cyvax\Contracts` | `PrivatebinPHP` (camelCase) |
| `PrivatebinSnakeCaseClientInterface` | `Cyvax\Contracts` | `PrivatebinSnakeCase`       |

Type-hint against an interface when you want to swap implementations:

```php
use Cyvax\Contracts\PrivatebinClientInterface;

function postPaste(PrivatebinClientInterface $client): array {
    return $client->setText("Hello!")->encodeAndPost();
}
```

---

# Features

* AES-256-GCM encryption
* PBKDF2 SHA-256 key derivation
* Zlib compression support
* Attachments support
* Password-protected pastes
* Burn-after-reading support
* Discussions support
* Multiple formatter support
* SSL verification control
* Fluent API
* Functional pipe helpers
* PHP 7.1+ compatible

---

# Documentation

See the [Wiki](https://github.com/cyvax/Privatebin_PHP/wiki) for full documentation.

---

# License

This project is licensed under the MIT license.

See [LICENSE](https://github.com/cyvax/Privatebin_PHP/blob/master/LICENSE).