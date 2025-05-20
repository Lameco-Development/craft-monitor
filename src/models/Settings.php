<?php

namespace lameco\monitor\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * monitor settings
 */
class Settings extends Model
{
    public array $feedme = [];
    public array $formie = [];
    public string $webhookUrl = '';

    public function rules(): array
    {
        return [
            [['feedme', 'formie'], 'safe'],
            ['feedme', 'validateFeedmeTable'],
            ['formie', 'validateFormieTable'],
        ];
    }

    public function getFeedmeTableDefinition(): array
    {
        $feeds = craft\feedme\Plugin::$plugin->getFeeds()->getFeeds();

        $options = [];
        foreach ($feeds as $feed) {
            $options[$feed->name] = $feed->name;
        }

        return [
            'columns' => [
                'target' => [
                    'heading' => 'Feed',
                    'type' => 'select',
                    'options' => $options,
                ],
                'threshold' => [
                    'heading' => 'Threshold',
                    'type' => 'number',
                    'inputAttributes' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
            ],
            'defaults' => [],
        ];
    }

    public function getFormieTableDefinition(): array
    {
        $forms = \verbb\formie\Formie::$plugin->getForms()->getAllForms();

        $options = [];
        foreach ($forms as $form) {
            $options[$form->handle] = $form->title . ' ('. $form->handle . ')';
        }

        return [
            'columns' => [
                'target' => [
                    'heading' => 'Form',
                    'type' => 'select',
                    'options' => $options,
                ],
                'threshold' => [
                    'heading' => 'Threshold',
                    'type' => 'number',
                    'inputAttributes' => [
                        'min' => 0,
                        'max' => 1,
                        'step' => 0.01,
                    ],
                ],
            ],
            'defaults' => [],
        ];
    }

    public function validateFeedmeTable($attribute)
    {
        $hasError = false;

        foreach ($this->$attribute as $i => $row) {
            if (empty($row['target'])) {
                $this->addError($attribute, Craft::t('app', "Row {i}: Feed is required.", ['i' => $i + 1]));
                $hasError = true;
            }
            if (!isset($row['threshold']) || $row['threshold'] === '') {
                $this->addError($attribute, Craft::t('app', "Row {i}: Threshold is required.", ['i' => $i + 1]));
                $hasError = true;
            } elseif (!is_numeric($row['threshold']) || $row['threshold'] < 0 || $row['threshold'] > 1) {
                $this->addError($attribute, Craft::t('app', "Row {i}: Threshold must be a number between 0 and 1.", ['i' => $i + 1]));
                $hasError = true;
            }
        }

        if (!$hasError && empty($this->$attribute)) {
            $this->addError($attribute, Craft::t('app', 'At least one row is required.'));
        }
    }

    public function validateFormieTable($attribute)
    {
        foreach ($this->$attribute as $i => $row) {
            if (empty($row['target'])) {
                $this->addError("{$attribute}[{$i}][target]", Craft::t('app', 'Form is required.'));
            }
            if (!isset($row['threshold']) || $row['threshold'] === '') {
                $this->addError("{$attribute}[{$i}][threshold]", Craft::t('app', 'Threshold is required.'));
            } elseif (!is_numeric($row['threshold']) || $row['threshold'] < 0 || $row['threshold'] > 1) {
                $this->addError("{$attribute}[{$i}][threshold]", Craft::t('app', 'Threshold must be a number between 0 and 1.'));
            }
        }
    }

    public function getWebhookUrl(): string
    {
        return App::parseEnv($this->webhookUrl);
    }
}
