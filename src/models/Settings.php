<?php

namespace fiveagency\craftprometheusexporter\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 * Prometheus Exporter settings
 */
class Settings extends Model
{
    public $metricsPath = '/metrics';
    public $basicAuthEnabled = false;
    public $basicAuthUsername = '';
    public $basicAuthPassword = '';

    protected function defineBehaviors(): array {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => [
                    'metricsPath',
                    'basicAuthEnabled',
                    'basicAuthUsername',
                    'basicAuthPassword'
                ]
            ]
        ];
    }

    public function defineRules(): array {
        return [
            [['metricsPath'], 'required'],
            [['basicAuthUsername'], 'required'],
            [['basicAuthPassword'], 'required'],
        ];
    }
}
