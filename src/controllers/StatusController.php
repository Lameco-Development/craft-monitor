<?php

namespace lameco\monitor\controllers;

use craft\web\Controller;
use lameco\monitor\Plugin;
use yii\web\Response;

class StatusController extends Controller
{
    protected array|int|bool $allowAnonymous = true;

    public function actionIndex(): Response
    {
        return $this->asJson([
            'targets' => Plugin::getInstance()->getMonitorRegistry()->getFlattenedTargets(),
        ]);
    }
}
