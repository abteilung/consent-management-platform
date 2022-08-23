<?php

declare(strict_types=1);

namespace App\Application\DataProcessor\Read\Reader;

use Throwable;
use App\Application\DataProcessor\Row;
use App\Application\DataProcessor\ArrayRowData;
use App\Application\DataProcessor\Exception\ReaderException;
use App\Application\DataProcessor\Read\Resource\QueryResource;
use SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface;
use SixtyEightPublishers\ArchitectureBundle\ReadModel\Query\Batch;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractUuidIdentity;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractValueObjectSet;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractEnumValueObject;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractArrayValueObject;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractStringValueObject;
use SixtyEightPublishers\ArchitectureBundle\Domain\ValueObject\AbstractIntegerValueObject;

final class QueryReader extends AbstractReader
{
	private QueryBusInterface $queryBus;

	/**
	 * @param \SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface $queryBus
	 * @param \App\Application\DataProcessor\Read\Resource\QueryResource     $resource
	 */
	protected function __construct(QueryBusInterface $queryBus, QueryResource $resource)
	{
		parent::__construct($resource);

		$this->queryBus = $queryBus;
	}

	/**
	 * @param \SixtyEightPublishers\ArchitectureBundle\Bus\QueryBusInterface $queryBus
	 * @param \App\Application\DataProcessor\Read\Resource\QueryResource     $resource
	 *
	 * @return static
	 */
	public static function create(QueryBusInterface $queryBus, QueryResource $resource): self
	{
		return new self($queryBus, $resource);
	}

	/**
	 * {@inheritDoc}
	 */
	protected function doRead(ErrorCallback $errorCallback): iterable
	{
		$resource = $this->resource;
		assert($resource instanceof QueryResource);

		$query = $resource->query();

		try {
			$i = 0;

			foreach ($this->queryBus->dispatch($query) as $row) {
				if (!$row instanceof Batch) {
					yield $this->createRow($i, (array) $row);

					$i++;

					continue;
				}

				foreach ($row->results() as $result) {
					yield $this->createRow($i, (array) $result);

					$i++;
				}
			}
		} catch (Throwable $e) {
			$errorCallback(ReaderException::invalidResource($e->getMessage()));
		}
	}

	/**
	 * @param int   $index
	 * @param array $row
	 *
	 * @return \App\Application\DataProcessor\Row
	 */
	private function createRow(int $index, array $row): Row
	{
		return Row::create(
			(string) $index,
			ArrayRowData::create($this->valueObjects2primitives($row))
		);
	}

	/**
	 * @param array $row
	 *
	 * @return array
	 */
	private function valueObjects2primitives(array $row): array
	{
		foreach ($row as $key => $value) {
			if ($value instanceof AbstractStringValueObject || $value instanceof AbstractIntegerValueObject || $value instanceof AbstractEnumValueObject) {
				$row[$key] = $value->value();

				continue;
			}

			if ($value instanceof AbstractArrayValueObject) {
				$row[$key] = $value->values();

				continue;
			}

			if ($value instanceof AbstractUuidIdentity) {
				$row[$key] = $value->toString();

				continue;
			}

			if ($value instanceof AbstractValueObjectSet) {
				$row[$key] = $value->toArray();

				continue;
			}

			if (is_array($value)) {
				$row[$key] = $this->valueObjects2primitives($value);
			}
		}

		return $row;
	}
}
