<?php

namespace lameco\monitor\monitors;

use craft\base\Event;
use craft\events\ModelEvent;
use lameco\monitor\Plugin;
use verbb\formie\elements\Submission;

class FormieMonitor extends AbstractMonitor
{
    private static bool $eventsRegistered = false;

    public function getHandle(): string
    {
        return 'formie';
    }

    public function getName(): string
    {
        return 'Formie';
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
            Submission::class,
            Submission::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                $form = $event->sender->getForm();

                if (!array_key_exists($form->handle, $this->getConfiguredTargets())) {
                    return;
                }

                Plugin::getInstance()->getMonitorService()->logActivity(
                    $this->getName(),
                    $form->handle,
                    1,
                );
            });
    }
}
