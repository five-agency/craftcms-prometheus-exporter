<?php

namespace fiveagency\craftprometheusexporter;

use Craft;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterUrlRulesEvent;
use craft\web\UrlManager;
use fiveagency\craftprometheusexporter\models\Settings;
use fiveagency\craftprometheusexporter\services\MetricsService;

/**
 * Prometheus Exporter plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @author FIVE Agency AG <support@five-agency.ch>
 * @copyright FIVE Agency AG
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read Metrics $metrics
 * @property-read MetricsService $metricsService
 */
class Plugin extends BasePlugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['metricsService' => MetricsService::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('prometheus-exporter/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules["/metrics"] = "prometheus-exporter/metrics/index";
            }
        );
    }
}
