<?php

namespace lameco\monitor;

use Craft;
use craft\base\Event;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\services\Utilities;
use craft\web\UrlManager;
use lameco\monitor\models\Settings;
use lameco\monitor\services\ExpectedService;
use lameco\monitor\services\MonitorRegistry;
use lameco\monitor\services\MonitorService;
use lameco\monitor\utilities\MonitorUtility;

/**
 * monitor plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 */
class Plugin extends BasePlugin
{
    public static Plugin $plugin;

    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'monitorRegistry' => MonitorRegistry::class,
                'monitorService' => MonitorService::class,
                'expectedService' => ExpectedService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        foreach ($this->getMonitorRegistry()->getMonitors() as $monitor) {
            $monitor->init();
        }

        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });

        // Register console commands
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            Craft::$app->controllerMap['monitor-check'] = \lameco\monitor\console\controllers\CheckController::class;
        }
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('_craft-monitor/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_SITE_URL_RULES,
            static function(RegisterUrlRulesEvent $event) {
                $event->rules['monitor/status'] = '_craft-monitor/status/index';
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITY_TYPES,
            static function (RegisterComponentTypesEvent $event) {
                $event->types[] = MonitorUtility::class;
            }
        );
    }

    public function getMonitorRegistry(): MonitorRegistry
    {
        return $this->get('monitorRegistry');
    }

    public function getMonitorService(): MonitorService
    {
        return $this->get('monitorService');
    }

    public function getExpectedService(): ExpectedService
    {
        return $this->get('expectedService');
    }
}
