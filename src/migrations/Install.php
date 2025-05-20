<?php

namespace lameco\monitor\migrations;

use craft\db\Migration;

class Install extends Migration
{
    public function safeUp(): bool
    {
        $this->createTable('{{%monitor_status}}', [
            'id' => $this->primaryKey(),
            'type' => $this->string()->notNull(),
            'target' => $this->string()->notNull(),
            'count' => $this->integer()->notNull(),
            'date' => $this->dateTime()->notNull(),
        ]);

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%monitor_status}}');

        return true;
    }
}
