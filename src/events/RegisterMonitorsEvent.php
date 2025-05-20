<?php

namespace lameco\monitor\events;

use yii\base\Event;

class RegisterMonitorsEvent extends Event
{
    public array $monitors = [];
}
