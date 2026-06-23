<?php

namespace fiveagency\craftprometheusexporter\services;

use fiveagency\craftprometheusexporter\Plugin;
use yii\base\Component;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

/**
 * Basic Auth Service service
 */
class BasicAuthService extends Component
{
    public function authenticate(string|null $authorization): void
    {
        $basicAuthEnabled = Plugin::getInstance()->settings->getBasicAuthEnabled();
        $basicAuthUser = Plugin::getInstance()->settings->getBasicAuthUsername();
        $basicAuthPassword = Plugin::getInstance()->settings->getBasicAuthPassword();

        if (!$basicAuthEnabled) {
            return;
        }

        if (!$authorization) {
            throw new UnauthorizedHttpException('credentials missing');
        }

        if (!str_starts_with($authorization, 'Basic ')) {
            throw new BadRequestHttpException('credentials malformed');
        }

        $authorizationBase64 = substr($authorization, 6);
        $authorizationDecoded = strtr(base64_decode($authorizationBase64), "\n", '');

        [$user, $password] = explode(':', $authorizationDecoded, 2);

        if ($user != $basicAuthUser || $password != $basicAuthPassword) {
            throw new ForbiddenHttpException('wrong username or password');
        }
    }
}
