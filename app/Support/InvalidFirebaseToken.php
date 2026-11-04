<?php

namespace App\Support;

/** Raised when a Firebase ID token cannot be trusted. The message is safe to show the person. */
class InvalidFirebaseToken extends \RuntimeException
{
}
