<?php

declare(strict_types=1);

namespace App\Domain\Project;

use App\Domain\Shared\ValueObject\Locale;
use App\Domain\Project\ValueObject\Template;

final class ProjectTranslation
{
	# autogenerated
	private string $id;

	private Project $project;

	private Locale $locale;

	private Template $template;

	private function __construct()
	{
	}

	/**
	 * @param \App\Domain\Project\Project              $project
	 * @param \App\Domain\Shared\ValueObject\Locale    $locale
	 * @param \App\Domain\Project\ValueObject\Template $template
	 *
	 * @return static
	 */
	public static function create(Project $project, Locale $locale, Template $template): self
	{
		$projectTranslation = new self();
		$projectTranslation->project = $project;
		$projectTranslation->locale = $locale;
		$projectTranslation->template = $template;

		return $projectTranslation;
	}

	/**
	 * @return \App\Domain\Project\Project
	 */
	public function project(): Project
	{
		return $this->project;
	}

	/**
	 * @return \App\Domain\Shared\ValueObject\Locale
	 */
	public function locale(): Locale
	{
		return $this->locale;
	}

	/**
	 * @return \App\Domain\Project\ValueObject\Template
	 */
	public function template(): Template
	{
		return $this->template;
	}

	/**
	 * @param \App\Domain\Project\ValueObject\Template $template
	 *
	 * @return void
	 */
	public function setTemplate(Template $template): void
	{
		$this->template = $template;
	}
}