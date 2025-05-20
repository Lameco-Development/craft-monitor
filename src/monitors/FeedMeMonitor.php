<?php

namespace lameco\monitor\monitors;

use craft\base\Event;
use craft\db\Query;
use craft\feedme\events\FeedProcessEvent;
use craft\feedme\services\Process;
use lameco\monitor\Plugin;

class FeedMeMonitor extends AbstractMonitor
{
    private static bool $eventsRegistered = false;
    private int $count = 0;

    public function getHandle(): string
    {
        return 'feedme';
    }

    public function getName(): string
    {
        return 'Feed Me';
    }

    public function getStatus(): array
    {
        $config = $this->getConfiguredTargets();
        $recentData = $this->getRecentData();

        return [
            'name' => $this->getName(),
            'handle' => $this->getHandle(),
            'targets' => $this->buildTargets($config, $recentData),
        ];
    }

    public function init(): void
    {
        if (self::$eventsRegistered) {
            return;
        }

        self::$eventsRegistered = true;

        Event::on(
            Process::class,
            Process::EVENT_STEP_AFTER_ELEMENT_SAVE,
            function (FeedProcessEvent $event) {
                if ($event->isValid) {
                    $this->count++;
                }
            });

        Event::on(
            Process::class,
            Process::EVENT_AFTER_PROCESS_FEED,
            function (FeedProcessEvent $event) {
                $feed = $event->feed;

                if (!array_key_exists($feed->name, $this->getConfiguredTargets())) {
                    return;
                }

                Plugin::getInstance()->getMonitorService()->logActivity(
                    $this->getName(),
                    $feed->name,
                    $this->count,
                );

                $this->count = 0;
            });
    }
}
