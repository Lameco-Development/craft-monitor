<?php

namespace lameco\monitor\services;

use craft\db\Query;

class ExpectedService
{
    /**
     * Get average count over N days for a given monitor target (feed/form/etc.)
     */
    public function getExpected(string $type, string $target): int
    {
        $rows = (new Query())
            ->select(['count'])
            ->from('{{%monitor_status}}')
            ->where([
                'type' => $type,
                'target' => $target,
            ])
            ->all();

        if (empty($rows)) {
            return 0;
        }

        $counts = array_column($rows, 'count');
        return (int)round(array_sum($counts) / count($counts));
    }

    /**
     * Get median count over N days (more resilient to spikes/outliers)
     */
    public function getMedianExpected(string $type, string $target): int
    {
        $rows = (new Query())
            ->select(['count'])
            ->from('{{%monitor_status}}')
            ->where([
                'type' => $type,
                'target' => $target,
            ])
            ->all();

        if (empty($rows)) {
            return 0;
        }

        $counts = array_map(fn($r) => (int)$r['count'], $rows);
        sort($counts);
        $count = count($counts);

        return $count % 2 === 0
            ? (int)round(($counts[$count / 2 - 1] + $counts[$count / 2]) / 2)
            : $counts[floor($count / 2)];
    }

    /**
     * Optional: get total count over N days
     */
    public function getTotal(string $type, string $target): int
    {
        return (int)(new Query())
            ->from('{{%monitor_status}}')
            ->where([
                'type' => $type,
                'target' => $target,
            ])
            ->sum('count');
    }
}
