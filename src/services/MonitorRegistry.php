<?php

namespace lameco\monitor\services;

use craft\base\Component;
use lameco\monitor\events\RegisterMonitorsEvent;
use lameco\monitor\monitors\FeedMeMonitor;
use lameco\monitor\monitors\MonitorInterface;
use lameco\monitor\monitors\FormieMonitor;

class MonitorRegistry extends Component
{
    public const EVENT_REGISTER_MONITORS = 'registerMonitors';

    /**
     * @return MonitorInterface[]
     */
    public function getMonitors(): array
    {
        $event = new RegisterMonitorsEvent([
            'monitors' => [
                new FeedMeMonitor(),
                new FormieMonitor(),
            ],
        ]);

        $this->trigger(self::EVENT_REGISTER_MONITORS, $event);

        return $event->monitors;
    }

    public function getAllTargets(): array
    {
        $targets = [];

        foreach ($this->getMonitors() as $monitor) {
            $data = $monitor->getStatus();
            foreach ($data['targets'] as $target) {
                $targets[$monitor->getHandle()][] = $target;
            }
        }

        return $targets;
    }

    public function getFlattenedTargets(): array
    {
        $targets = [];

        foreach ($this->getMonitors() as $monitor) {
            $data = $monitor->getStatus();
            foreach ($data['targets'] as $target) {
                $targets[] = $target;
            }
        }

        return $targets;
    }
}
