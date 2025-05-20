<?php

namespace lameco\monitor\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
use lameco\monitor\Plugin;
use yii\console\ExitCode;

class CheckController extends Controller
{
    public function actionIndex(): int
    {
        $settings = Plugin::getInstance()->getSettings();

        if (empty($settings->getWebhookUrl())) {
            $this->stderr('No webhook URL configured. Skipping status check.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout('Checking monitor statuses...' . PHP_EOL);

        $targets = Plugin::getInstance()->getMonitorRegistry()->getFlattenedTargets();
        $alerts = [];

        foreach ($targets as $target) {
            if ($target['status'] === 'ALERT') {
                $alerts[] = $target;
            }
        }

        if (empty($alerts)) {
            $this->stdout('All monitors are OK.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        $this->stdout('Found ' . count($alerts) . ' alerts:' . PHP_EOL, Console::FG_RED);

        foreach ($alerts as $alert) {
            $this->stdout(sprintf(
                '- %s (%s): %s' . PHP_EOL,
                $alert['target'],
                $alert['type'],
                $alert['reason']
            ), Console::FG_RED);
        }

        // Send webhook notification
        $client = Craft::createGuzzleClient();
        try {
            $response = $client->post($settings->getWebhookUrl(), [
                'json' => [
                    'site' => [
                        'name' => getenv('SITE_NAME') ?: Craft::$app->getSystemName(),
                        'url' => getenv('PRIMARY_SITE_URL') ?: Craft::$app->getSites()->getCurrentSite()->getBaseUrl(),
                        'environment' => getenv('CRAFT_ENVIRONMENT') ?: 'production',
                    ],
                    'check' => [
                        'timestamp' => date('c'),
                        'date' => date('Y-m-d H:i:s'),
                        'timezone' => date_default_timezone_get(),
                    ],
                    'alerts' => $alerts,
                    'summary' => [
                        'total_alerts' => count($alerts),
                        'total_monitors' => count($targets),
                        'alert_types' => array_count_values(array_column($alerts, 'type')),
                    ],
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $this->stdout('Webhook notification sent successfully.' . PHP_EOL, Console::FG_GREEN);
            } else {
                $this->stderr('Failed to send webhook notification. Status code: ' . $response->getStatusCode() . PHP_EOL, Console::FG_RED);
            }
        } catch (\Throwable $e) {
            $this->stderr('Error sending webhook notification: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
