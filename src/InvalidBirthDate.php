<?php

declare(strict_types=1);

namespace BirthStars;

/** Thrown for dates we cannot use; the message is safe and friendly to show to a child. */
final class InvalidBirthDate extends \InvalidArgumentException
{
}
