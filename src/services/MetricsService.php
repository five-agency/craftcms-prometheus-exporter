<?php

namespace fiveagency\craftprometheusexporter\services;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\App;
use craft\services\Updates;
use yii\base\Component;
/**
 * Metrics Service service
 */
class MetricsService extends Component
{
    private function generateLabels(array $labels): string {
        $delimiter = ',';

        $labelsDefault = [
            "base_url" => Craft::$app->sites->primarySite->baseUrl
        ];

        $labelsMerged = $labelsDefault + $labels;

        $labelsFormatted = [];

        foreach ($labelsMerged as $key => $value) {
            $labelsFormatted[] = "${key}=\"${value}\"";
        }

        return implode($delimiter, $labelsFormatted);
    }

    private function generateStats(string $key, array $labels, int $value): string {
        return "{$key}{{$this->generateLabels($labels)}} {$value}";
    }

    private function generateDocument(array $stats): string {
        $document = '';

        foreach($stats as $stat) {
            $document .= $stat . PHP_EOL;
        }

        return $document;
    }

    public function generateMetrics (): string {
        # CMS info
        # add db driver
        # add mailer info
        # add admin changes
        # add app id
        # CRAFT_IMAGE_DRIVER
        $craftcmsInfo = $this->generateStats('craftcms_info',
        [
            "cms_version" => Craft::$app->version,
            "cms_edition" => Craft::$app->edition->name,
            "dev_mode" => App::devMode(),
            "uname_name" => php_uname('s'),
            "uname_hostname" => php_uname('n'),
            "uname_release" => php_uname('r'),
            "uname_machine" => php_uname('m'),
            "php_version" => PHP_VERSION
        ], 1);

        #move the following to counters
        # dev_mode
        # backup_on_update
        # disallow robots
        # CRAFT_ENABLE_CSRF_COOKIE
        # CRAFT_ENABLE_CSRF_PROTECTION=
        # CRAFT_HEADLESS_MODE
        # CRAFT_MAX_UPLOAD_FILE_SIZE

        # Update stats
        $updatesService = new Updates();

        $craftcmsUpdatesTotalAvailable = $this->generateStats('craftcms_updates_total', [], $updatesService->totalAvailableUpdates);
        $craftcmsUpdatesCritical = $this->generateStats('craftcms_updates_critical', [], $updatesService->isCriticalUpdateAvailable);
        # add pending counters and more

        # Queue stats
        $craftcmsQueueTotal = $this->generateStats('craftcms_queue', [ "status" => 'all'], Craft::$app->getQueue()->totalJobs);
        $craftcmsQueueTotalReserved = $this->generateStats('craftcms_queue', ["status" => 'reserved'], Craft::$app->getQueue()->totalReserved);
        $craftcmsQueueTotalWaiting = $this->generateStats('craftcms_queue', ["status" => 'waiting'], Craft::$app->getQueue()->totalWaiting);
        $craftcmsQueueTotalDelayed = $this->generateStats('craftcms_queue', ["status" => 'delayed'], Craft::$app->getQueue()->totalDelayed);
        $craftcmsQueueTotalFailed = $this->generateStats('craftcms_queue', ["status" => 'failed'], Craft::$app->getQueue()->totalFailed);

        # Site stats
        $craftcmsSitesTotal = $this->generateStats('craftcms_num_sites', [], Craft::$app->sites->totalSites);
        # add max sites stats

        # User stats
        $craftcmsUsersTotalActive = $this->generateStats('craftcms_num_users', ['status' => 'active'], User::find()->status('active')->count());
        $craftcmsUsersTotalPending = $this->generateStats('craftcms_num_users', ['status' => 'pending'], User::find()->status('pending')->count());
        $craftcmsUsersTotalSuspended = $this->generateStats('craftcms_num_users', ['status' => 'suspended'], User::find()->status('suspended')->count());
        $craftcmsUsersTotalLocked = $this->generateStats('craftcms_num_users', ['status' => 'locked'], User::find()->status('locked')->count());
        $craftcmsUsersTotalInactive = $this->generateStats('craftcms_num_users', ['status' => 'inactive'], User::find()->status('inactive')->count());
        $craftcmsUsersTotalDraft = $this->generateStats('craftcms_num_users', ['status' => 'draft'], User::find()->drafts()->count());
        $craftcmsUsersTotalTrashed = $this->generateStats('craftcms_num_users', ['status' => 'trashed'], User::find()->trashed()->count());
        $craftcmsUsersTotalAdmin = $this->generateStats('craftcms_num_users', ['role' => 'admin'], User::find()->admin()->count());

        # Entries stats
        $craftcmsEntriesTotalLive = $this->generateStats('craftcms_num_entries', ['status' => 'live'], Entry::find()->status('live')->count());
        $craftcmsEntriesTotalDraft = $this->generateStats('craftcms_num_entries', ['status' => 'draft'], Entry::find()->drafts()->count());
        $craftcmsEntriesTotalPending = $this->generateStats('craftcms_num_entries', ['status' => 'pending'], Entry::find()->status('pending')->count());
        $craftcmsEntriesTotalExpired = $this->generateStats('craftcms_num_entries', ['status' => 'expired'], Entry::find()->status('expired')->count());
        $craftcmsEntriesTotalTrashed = $this->generateStats('craftcms_num_entries', ['status' => 'trashed'], Entry::find()->trashed()->count());

        # Asset stats
        $craftcmsAssetsTotalActive = $this->generateStats('craftcms_num_assets', ['status' => 'enabled'], Asset::find()->count());
        $craftcmsAssetsTotalActiveSize = $this->generateStats('craftcms_bytes_assets', ['status' => 'enabled'], Asset::find()->select('size')->scalar());

        # Deprecation stats
        $craftcmsDeprecationsTotal = $this->generateStats('craftcms_num_deprecations', [], Craft::$app->getDeprecator()->getTotalLogs());

        # Project config stats
        # Volumes stats
        # Add check if craft is off
        # When commerce is installed output order by status revenue and unpaid amount

        return $this->generateDocument([
            $craftcmsInfo,
            $craftcmsUpdatesTotalAvailable,
            $craftcmsUpdatesCritical,
            $craftcmsQueueTotal,
            $craftcmsQueueTotalReserved,
            $craftcmsQueueTotalWaiting,
            $craftcmsQueueTotalFailed,
            $craftcmsSitesTotal,
            $craftcmsUsersTotalDraft,
            $craftcmsUsersTotalActive,
            $craftcmsUsersTotalPending,
            $craftcmsUsersTotalSuspended,
            $craftcmsUsersTotalLocked,
            $craftcmsUsersTotalInactive,
            $craftcmsUsersTotalTrashed,
            $craftcmsUsersTotalAdmin,
            $craftcmsEntriesTotalLive,
            $craftcmsEntriesTotalDraft,
            $craftcmsEntriesTotalPending,
            $craftcmsEntriesTotalExpired,
            $craftcmsEntriesTotalTrashed,
            $craftcmsAssetsTotalActive,
            $craftcmsAssetsTotalActiveSize,
            $craftcmsDeprecationsTotal
        ]);
    }
}
