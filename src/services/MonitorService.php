<?php

namespace lameco\monitor\services;

use Craft;
use yii\db\Expression;

class MonitorService
{
    public function logActivity(string $type, string $target, int $count): void
    {
        Craft::$app->db->createCommand()->insert('{{%monitor_status}}', [
            'type' => $type,
            'target' => $target,
            'count' => $count,
            'date' => new Expression('NOW()'),
        ])->execute();
    }
}
