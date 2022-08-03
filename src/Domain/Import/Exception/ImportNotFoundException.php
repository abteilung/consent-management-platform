<?php

declare(strict_types=1);

namespace App\Domain\Import\Exception;

use DomainException;
use App\Domain\Import\ValueObject\ImportId;

final class ImportNotFoundException extends DomainException
{
	/**
	 * @param \App\Domain\Import\ValueObject\ImportId $id
	 *
	 * @return static
	 */
	public static function withId(ImportId $id): self
	{
		return new self(sprintf(
			'Import with ID %s not found.',
			$id
		));
	}
}