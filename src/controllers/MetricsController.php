<?php

namespace fiveagency\craftprometheusexporter\controllers;

use Craft;
use craft\web\Controller;
use fiveagency\craftprometheusexporter\services\BasicAuthService;
use fiveagency\craftprometheusexporter\services\MetricsService;
use yii\web\Response;

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
        $basicAuthService = new BasicAuthService();
        $basicAuthService->authenticate($this->request->getHeaders()->get('Authorization'));

        $metricsService = new MetricsService();
        $metrics = $metricsService->generateMetrics();

        Craft::$app->response->format = Response::FORMAT_RAW;

        $this->response->getHeaders()->set('Content-Type', 'text/plain; version=1.0.0');
        $this->response->content = $metrics;

        return $this->response;
    }
}
