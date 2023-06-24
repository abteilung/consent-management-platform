<?php

declare(strict_types=1);

namespace App\Web\AdminModule\ApplicationModule\Presenter;

use App\Web\Ui\Form\FormFactoryInterface;
use App\Web\AdminModule\Presenter\AdminPresenter;
use App\Application\Acl\ApplicationSettingsResource;
use SixtyEightPublishers\FlashMessageBundle\Domain\FlashMessage;
use SixtyEightPublishers\SmartNetteComponent\Annotation\IsAllowed;
use App\Web\AdminModule\ApplicationModule\Control\CrawlerSettingsForm\CrawlerSettingsFormControl;
use App\Web\AdminModule\ApplicationModule\Control\ApiCacheSettingsForm\ApiCacheSettingsFormControl;
use App\Web\AdminModule\ApplicationModule\Control\CrawlerSettingsForm\Event\CrawlerSettingsUpdatedEvent;
use App\Web\AdminModule\ApplicationModule\Control\ApiCacheSettingsForm\Event\ApiCacheSettingsUpdatedEvent;
use App\Web\AdminModule\ApplicationModule\Control\LocalizationSettingsForm\LocalizationSettingsFormControl;
use App\Web\AdminModule\ApplicationModule\Control\CrawlerSettingsForm\Event\CrawlerSettingsUpdateFailedEvent;
use App\Web\AdminModule\ApplicationModule\Control\ApiCacheSettingsForm\Event\ApiCacheSettingsUpdateFailedEvent;
use App\Web\AdminModule\ApplicationModule\Control\CrawlerSettingsForm\CrawlerSettingsFormControlFactoryInterface;
use App\Web\AdminModule\ApplicationModule\Control\LocalizationSettingsForm\Event\LocalizationSettingsUpdatedEvent;
use App\Web\AdminModule\ApplicationModule\Control\ApiCacheSettingsForm\ApiCacheSettingsFormControlFactoryInterface;
use App\Web\AdminModule\ApplicationModule\Control\LocalizationSettingsForm\Event\LocalizationSettingsUpdateFailedEvent;
use App\Web\AdminModule\ApplicationModule\Control\LocalizationSettingsForm\LocalizationSettingsFormControlFactoryInterface;

/**
 * @IsAllowed(resource=ApplicationSettingsResource::class, privilege=ApplicationSettingsResource::UPDATE)
 */
final class SettingsPresenter extends AdminPresenter
{
	private LocalizationSettingsFormControlFactoryInterface $localizationSettingsFormControlFactory;

	private ApiCacheSettingsFormControlFactoryInterface $apiCacheSettingsFormControlFactory;

	private CrawlerSettingsFormControlFactoryInterface $crawlerSettingsFormControlFactory;

	public function __construct(
		LocalizationSettingsFormControlFactoryInterface $localizationSettingsFormControlFactory,
		ApiCacheSettingsFormControlFactoryInterface $apiCacheSettingsFormControlFactory,
		CrawlerSettingsFormControlFactoryInterface $crawlerSettingsFormControlFactory
	) {
		parent::__construct();

		$this->localizationSettingsFormControlFactory = $localizationSettingsFormControlFactory;
		$this->apiCacheSettingsFormControlFactory = $apiCacheSettingsFormControlFactory;
		$this->crawlerSettingsFormControlFactory = $crawlerSettingsFormControlFactory;
	}

	protected function createComponentLocalizationForm(): LocalizationSettingsFormControl
	{
		$control = $this->localizationSettingsFormControlFactory->create();

		$control->setFormFactoryOptions([
			FormFactoryInterface::OPTION_AJAX => TRUE,
		]);

		$control->addEventListener(LocalizationSettingsUpdatedEvent::class, function (): void {
			$this->subscribeFlashMessage(FlashMessage::success('localization_settings_updated'));
			$this->redrawControl('before_content');
		});

		$control->addEventListener(LocalizationSettingsUpdateFailedEvent::class, function (): void {
			$this->subscribeFlashMessage(FlashMessage::error('localization_settings_update_failed'));
		});

		return $control;
	}

	protected function createComponentApiCacheForm(): ApiCacheSettingsFormControl
	{
		$control = $this->apiCacheSettingsFormControlFactory->create();

		$control->setFormFactoryOptions([
			FormFactoryInterface::OPTION_AJAX => TRUE,
		]);

		$control->addEventListener(ApiCacheSettingsUpdatedEvent::class, function (): void {
			$this->subscribeFlashMessage(FlashMessage::success('api_cache_settings_updated'));
			$this->redrawControl('localization');
			$this->redrawControl('before_content');
		});

		$control->addEventListener(ApiCacheSettingsUpdateFailedEvent::class, function (): void {
			$this->subscribeFlashMessage(FlashMessage::error('api_cache_settings_update_failed'));
		});

		return $control;
	}

	protected function createComponentCrawlerForm(): CrawlerSettingsFormControl
	{
		$control = $this->crawlerSettingsFormControlFactory->create();

		$control->setFormFactoryOptions([
			FormFactoryInterface::OPTION_AJAX => TRUE,
		]);

		$control->addEventListener(CrawlerSettingsUpdatedEvent::class, function (): void {
			$this->subscribeFlashMessage(FlashMessage::success('crawler_settings_updated'));
		});

		$control->addEventListener(CrawlerSettingsUpdateFailedEvent::class, function (): void {
			$this->subscribeFlashMessage(FlashMessage::error('crawler_settings_update_failed'));
		});

		return $control;
	}
}
