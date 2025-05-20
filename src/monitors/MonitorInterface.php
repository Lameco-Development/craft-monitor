<?php

namespace lameco\monitor\monitors;

interface MonitorInterface
{
    public function getHandle(): string;

    public function getName(): string;

    public function getStatus(): array;

    public function init(): void;
}
