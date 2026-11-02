<?php

declare(strict_types=1);

namespace Dilemma;

final class Failure extends \RuntimeException
{
	public function __construct(public readonly int $status, public readonly string $error, public readonly array $details = [])
	{
		parent::__construct($error);
	}
}
