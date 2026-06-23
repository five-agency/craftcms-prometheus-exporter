<?php

namespace fiveagency\craftprometheusexporter\services;

use Craft;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\App;
use craft\services\Plugins;
use craft\services\Updates;
use yii\base\Component;
/**
 * Metrics Service service
 */
class MetricsService extends Component
{
    private const GAUGE = 'gauge';
    private const COUNTER = 'counter';
    private const HISTOGRAM = 'histogram';
    private const SUMMARY = 'summary';
    private const PREFIX = 'craftcms_';

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

    private function generateStats(string $key, array $labels, int $value, string|null $help, string|null $type): string {
        $help = $help ? '#HELP ' . self::PREFIX . $key . ' ' . $help . PHP_EOL : '';
        $type = $type ? '#TYPE ' . self::PREFIX . $key . ' ' . $type . PHP_EOL : '';
        $stat = self::PREFIX . "{$key}{{$this->generateLabels($labels)}} {$value}";

        return $help . $type .$stat;
    }

    private function generateDocument(array $stats): string {
        $document = '';

        foreach($stats as $stat) {
            $document .= $stat . PHP_EOL;
        }

        return $document;
    }

    public function generateMetrics (): string {
        $info = $this->generateStats('info',
        [
            "cms_version" => Craft::$app->version,
            "cms_edition" => Craft::$app->edition->name,
            "app_id" => Craft::$app->id,
            "dev_mode" => App::devMode(),
            "uname_name" => php_uname('s'),
            "uname_hostname" => php_uname('n'),
            "uname_release" => php_uname('r'),
            "uname_machine" => php_uname('m'),
            "php_version" => PHP_VERSION
        ], 1, 'Generic info', self::GAUGE);

        # Update stats
        $updatesService = new Updates();

        $updatesAvailable = $this->generateStats('updates', [], $updatesService->totalAvailableUpdates, 'Number of CMS/plugin updates', self::GAUGE);
        $updatesAvailableCritical = $this->generateStats('updates_critical', [], $updatesService->isCriticalUpdateAvailable, 'If a critical update is available', self::GAUGE);
        $updatesPending = $this->generateStats('updates_pending', [], $updatesService->getIsUpdatePending(), 'If the cms is currently updated', self::GAUGE);
        // # add pending counters and more

        # Queue stats
        $queueTotal = $this->generateStats('queue', [ "status" => 'all'], Craft::$app->getQueue()->totalJobs, 'Number of queue jobs', self::COUNTER);
        $queueTotalReserved = $this->generateStats('queue', ["status" => 'reserved'], Craft::$app->getQueue()->totalReserved, null, null);
        $queueTotalWaiting = $this->generateStats('queue', ["status" => 'waiting'], Craft::$app->getQueue()->totalWaiting, null, null);
        $queueTotalDelayed = $this->generateStats('queue', ["status" => 'delayed'], Craft::$app->getQueue()->totalDelayed, null, null);
        $queueTotalFailed = $this->generateStats('queue', ["status" => 'failed'], Craft::$app->getQueue()->totalFailed, null, null);

        # Site stats
        $sitesTotal = $this->generateStats('num_sites', [], Craft::$app->sites->totalSites, 'Number of sites for this installation', self::COUNTER);
        # add max sites stats

        // # User stats
        $usersTotalActive = $this->generateStats('num_users', ['status' => 'active'], User::find()->status('active')->count(), 'Number of users', self::COUNTER);
        $usersTotalPending = $this->generateStats('num_users', ['status' => 'pending'], User::find()->status('pending')->count(), null, null);
        $usersTotalSuspended = $this->generateStats('num_users', ['status' => 'suspended'], User::find()->status('suspended')->count(), null, null);
        $usersTotalLocked = $this->generateStats('num_users', ['status' => 'locked'], User::find()->status('locked')->count(), null, null);
        $usersTotalInactive = $this->generateStats('num_users', ['status' => 'inactive'], User::find()->status('inactive')->count(), null, null);
        $usersTotalDraft = $this->generateStats('num_users', ['status' => 'draft'], User::find()->drafts()->count(), null, null);
        $usersTotalTrashed = $this->generateStats('num_users', ['status' => 'trashed'], User::find()->trashed()->count(), null, null);
        $usersTotalAdmin = $this->generateStats('num_users', ['role' => 'admin'], User::find()->admin()->count(), null, null);

        // # Entries stats
        $entriesTotalLive = $this->generateStats('num_entries', ['status' => 'live'], Entry::find()->status('live')->site('*')->count(), 'Number of entries', self::COUNTER);
        $entriesTotalDraft = $this->generateStats('num_entries', ['status' => 'draft'], Entry::find()->drafts()->site('*')->count(), null, null);
        $entriesTotalPending = $this->generateStats('num_entries', ['status' => 'pending'], Entry::find()->status('pending')->site('*')->count(), null, null);
        $entriesTotalExpired = $this->generateStats('num_entries', ['status' => 'expired'], Entry::find()->status('expired')->site('*')->count(), null, null);
        $entriesTotalTrashed = $this->generateStats('num_entries', ['status' => 'trashed'], Entry::find()->trashed()->site('*')->count(), null, null);

        # Asset stats
        $assetsTotalActive = $this->generateStats('num_assets', ['status' => 'enabled'], Asset::find()->count(), 'Total number of assets', self::COUNTER);
        $assetsTotalActiveSize = $this->generateStats('bytes_assets', ['status' => 'enabled'], Asset::find()->limit(null)->sum('size'), 'Total bytes of all assets', self::COUNTER);

        # Deprecation stats
        $deprecationsTotal = $this->generateStats('num_deprecations', [], Craft::$app->getDeprecator()->getTotalLogs(), 'Number of deprecation warnings', self::COUNTER);

        #Plugin stats
        $pluginService = new Plugins();

        $pluginsTotal = $this->generateStats('num_plugins', [], count($pluginService->getAllPlugins()), 'Number of plugins', self::COUNTER);

        $basicStats = [
            $info,
            $updatesAvailable,
            $updatesAvailableCritical,
            $updatesPending,
            $queueTotal,
            $queueTotalReserved,
            $queueTotalWaiting,
            $queueTotalFailed,
            $sitesTotal,
            $usersTotalDraft,
            $usersTotalActive,
            $usersTotalPending,
            $usersTotalSuspended,
            $usersTotalLocked,
            $usersTotalInactive,
            $usersTotalTrashed,
            $usersTotalAdmin,
            $entriesTotalLive,
            $entriesTotalDraft,
            $entriesTotalPending,
            $entriesTotalExpired,
            $entriesTotalTrashed,
            $assetsTotalActive,
            $assetsTotalActiveSize,
            $deprecationsTotal,
            $pluginsTotal
        ];

        #CraftCommerce stats
        $commerceStats = [];
        $commerceInstalled = $pluginService->getPlugin('commerce');

        if ($commerceInstalled) {
            #add all metrics regarding craft commerce
            # including
            # Order Number by status
            # Active and inactive carts
            # Total revenue
            # revenue by gateway
            # total donations
            # total revenue by subscriptions
        }

        return $this->generateDocument($basicStats + $commerceStats);
    }
}
