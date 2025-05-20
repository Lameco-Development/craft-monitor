<?php

namespace lameco\monitor\monitors;

use craft\base\Component;
use craft\db\Query;
use lameco\monitor\Plugin;

abstract class AbstractMonitor extends Component implements MonitorInterface
{
    abstract public function getHandle(): string;

    abstract public function getName(): string;

    abstract public function getStatus(): array;

    public function getConfiguredTargets(): array
    {
        $settings = Plugin::getInstance()->getSettings();

        $targets = [];

        foreach ($settings->{$this->getHandle()} ?? [] as $target) {
            if (!empty($target['target'])) {
                $targets[$target['target']] = $target;
            }
        }

        return $targets;
    }

    public function getRecentData(): array
    {
        $configured = array_keys($this->getConfiguredTargets());

        $rows = (new Query())
            ->select(['target', 'SUM(count) AS total', 'MAX(date) AS lastUpdate'])
            ->from('{{%monitor_status}}')
            ->where(['type' => $this->getHandle()])
            ->andWhere(['IN', 'target', $configured])
            ->andWhere(['>=', 'date', (new \DateTime('-24 hours'))->format(DATE_ATOM)])
            ->groupBy('target')
            ->all();

        // If no recent data, get the last available row with only target and lastUpdate
        if (empty($rows)) {
            $rows = (new Query())
                ->select(['target', 'MAX(date) AS lastUpdate'])
                ->from('{{%monitor_status}}')
                ->where(['type' => $this->getHandle()])
                ->andWhere(['IN', 'target', $configured])
                ->groupBy('target')
                ->all();
        }

        return array_column($rows, null, 'target');
    }

    public function buildTargets(array $config, array $recentData): array
    {
        $targets = [];

        foreach ($config as $target => $cfg) {
            $threshold = (float)($cfg['threshold'] ?? 0.75);
            $expected = Plugin::getInstance()->getExpectedService()->getMedianExpected($this->getHandle(), $target);

            $actual = $recentData[$target]['total'] ?? 0;
            $lastUpdated = $recentData[$target]['lastUpdate'] ?? null;

            $status = 'OK';
            $reason = null;

            if ($expected > 0 && $actual < $expected * $threshold) {
                $status = 'ALERT';
                $reason = "Only $actual in last 24h (expected ~{$expected})";
            }

            $targets[] = [
                'target' => $target,
                'type' => $this->getHandle(),
                'lastUpdated' => $lastUpdated ? date('Y-m-d H:i:s', strtotime($lastUpdated)) : null,
                'count' => $actual,
                'expected' => $expected,
                'status' => $status,
                'reason' => $reason,
            ];
        }

        return $targets;
    }
}
