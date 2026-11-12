<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * A phase could not be closed or reopened. The message is already
 * translated and safe to show the owner as is.
 */
final class PhaseException extends RuntimeException {}
