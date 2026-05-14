<?php

namespace Cyvax\Exceptions;

use Exception;

class PrivatebinException extends Exception {}

// Backward-compatibility alias.
class_alias(PrivatebinException::class, \Cyvax\PrivatebinException::class);
