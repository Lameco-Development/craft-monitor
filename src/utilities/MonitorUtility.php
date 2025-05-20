<?php

namespace lameco\monitor\utilities;

use Craft;
use craft\base\Utility;
use lameco\monitor\Plugin;

class MonitorUtility extends Utility
{
    public static function id(): string
    {
        return 'monitor-utility';
    }

    public static function displayName(): string
    {
        return 'Laméco Monitor';
    }

    public static function iconPath(): ?string
    {
        return Craft::getAlias('@appicons/alert.svg');
    }

    public static function contentHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('_craft-monitor/_utility', [
            'allTargets' => Plugin::getInstance()->getMonitorRegistry()->getAllTargets()
        ]);
    }
}
