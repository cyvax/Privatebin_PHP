![GitHub License](https://img.shields.io/github/license/cyvax/Privatebin_PHP?style=for-the-badge)
![version](https://img.shields.io/github/v/tag/cyvax/Privatebin_PHP?label=VERSION&style=for-the-badge)
![Codacy grade](https://img.shields.io/codacy/grade/13bbc8be0d134180b8221f014af50e74?style=for-the-badge)

Privatebin_PHP 
-----

Privatebin_PHP is api for [PrivateBin](https://github.com/PrivateBin/PrivateBin/) written on PHP.

Installing
-----
just download Privatebin_PHP.php

Dependencies
-----
[Tuupola Base58](https://github.com/tuupola/base58) : a Base58 encoder by Tuupola

Usage
-----
By default Privatebin_PHP configured to use `https://paste.i2pd.xyz/` for sending and receiving pastes.

You can parse config to a PrivatebinPHP object.

Example :<br>
fast one with options passed as argument on creation : 
```php
$private =new PrivatebinPHP(array(
    "url" => "https://privatebin.net/",
    "text" => "Because ignorance is bliss!",
));
$posted = $private->encode_and_post();
```

Check [Wiki](https://github.com/cyvax/Privatebin_PHP/wiki) for documentation.<br>
It will send string `Because ignorance is bliss!` to [PrivateBin](https://privatebin.net/).

License
-------
This project is licensed under the MIT license, which can be found in the file
[LICENSE](https://github.com/cyvax/Privatebin_PHP/blob/master/LICENSE) in the root of the project source code.

