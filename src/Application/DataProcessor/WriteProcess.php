<?php

declare(strict_types=1);

namespace App\Application\DataProcessor;

use App\Application\DataProcessor\Read\Reader\ReaderInterface;
use App\Application\DataProcessor\Description\DescriptorInterface;
use App\Application\DataProcessor\Write\DataWriterFactoryInterface;
use App\Application\DataProcessor\Write\Resource\ResourceInterface;
use App\Application\DataProcessor\Write\Destination\StringDestination;
use App\Application\DataProcessor\Write\Destination\DestinationInterface;

final class WriteProcess
{
	private DataWriterFactoryInterface $dataWriterFactory;

	private ReaderInterface $reader;

	private ?DescriptorInterface $descriptor;

	private $onReaderError;

	/**
	 * @param \App\Application\DataProcessor\Write\DataWriterFactoryInterface     $dataWriterFactory
	 * @param \App\Application\DataProcessor\Read\Reader\ReaderInterface          $reader
	 * @param \App\Application\DataProcessor\Description\DescriptorInterface|null $descriptor
	 * @param callable|NULL                                                       $onReaderError
	 */
	public function __construct(DataWriterFactoryInterface $dataWriterFactory, ReaderInterface $reader, ?DescriptorInterface $descriptor = NULL, ?callable $onReaderError = NULL)
	{
		$this->dataWriterFactory = $dataWriterFactory;
		$this->reader = $reader;
		$this->descriptor = $descriptor;
		$this->onReaderError = $onReaderError;
	}

	/**
	 * @param \App\Application\DataProcessor\Description\DescriptorInterface $descriptor
	 *
	 * @return $this
	 */
	public function withDescriptor(DescriptorInterface $descriptor): self
	{
		return new self($this->dataWriterFactory, $this->reader, $descriptor, $this->onReaderError);
	}

	/**
	 * @param callable $onReaderError
	 *
	 * @return $this
	 */
	public function withReaderErrorCallback(callable $onReaderError): self
	{
		return new self($this->dataWriterFactory, $this->reader, $this->descriptor, $onReaderError);
	}

	/**
	 * @param string                                                                $format
	 * @param \App\Application\DataProcessor\Write\Destination\DestinationInterface $destination
	 *
	 * @return \App\Application\DataProcessor\Write\Destination\DestinationInterface
	 */
	public function write(string $format, DestinationInterface $destination): DestinationInterface
	{
		return $this->dataWriterFactory->toDestination($format, $this->createResource(), $destination)->write();
	}

	/**
	 * @param string $format
	 * @param string $filename
	 * @param array  $options
	 *
	 * @return void
	 */
	public function writeToFile(string $format, string $filename, array $options = []): void
	{
		$this->dataWriterFactory->toFile($format, $this->createResource(), $filename, $options)->write();
	}

	/**
	 * @param string $format
	 * @param array  $options
	 *
	 * @return string
	 */
	public function writeToString(string $format, array $options = []): string
	{
		$destination = $this->dataWriterFactory->toString($format, $this->createResource(), $options)->write();
		assert($destination instanceof StringDestination);

		return $destination->string();
	}

	/**
	 * @return \App\Application\DataProcessor\Write\Resource\ResourceInterface
	 */
	private function createResource(): ResourceInterface
	{
		return $this->reader->toWritableResource($this->descriptor, $this->onReaderError);
	}
}
