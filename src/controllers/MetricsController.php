<?php

namespace fiveagency\craftprometheusexporter\controllers;

use Craft;
use craft\web\Controller;
use yii\web\Response;
use fiveagency\craftprometheusexporter\services\MetricsService;

/**
 * Metrics controller
 */
class MetricsController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_LIVE;

    /**
     * prometheus-exporter/metrics action
     */
    public function actionIndex(): Response
    {
        $metricsService = new MetricsService();
        $metrics = $metricsService->generateMetrics();

        Craft::$app->response->format = Response::FORMAT_RAW;

        $this->response->getHeaders()->set('Content-Type', 'text/plain; version=1.0.0');
        $this->response->content = $metrics;

        return $this->response;
    }
}
