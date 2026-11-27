<?php

/**
 * Focused fixture-only coverage for the background updater UI.
 *
 * Validates: Requirements 1.1, 1.2, 1.4, 2.3, 2.4, 4.1, 4.2, 4.4,
 * 4.5, 4.6, 5.4, 6.1, 6.2, 6.3, 6.4
 */
class BackgroundUpdateProgressCest
{
    private const SYSTEM_UPDATE_TITLE = 'Background Process - SystemUpdateProcess';
    private const OTHER_PROCESS_TITLE = 'Background Process - AcceptanceOtherProcess';

    public function _before(AcceptanceTester $I): void
    {
        // Use only disposable Codeception database records. No update action is
        // submitted unless it is already suppressed by an active fixture.
        $I->deleteFromDatabase('gibbonLog', ['title' => self::SYSTEM_UPDATE_TITLE]);
        $I->deleteFromDatabase('gibbonLog', ['title' => self::OTHER_PROCESS_TITLE]);
    }

    public function activeProgressAppearsAfterOverviewAndPollsJsonInPlace(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'database', 'installing', 'working', 70);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');
        $I->see('Installing update…', '#updateProgress');
        $I->seeElement('#updateProgress', [
            'x-show' => 'updateProgressOpen',
            'role' => 'dialog',
            'aria-modal' => 'true',
        ]);


        // The worker only changes the shared snapshot. The fixed modal DOM
        // consumes the safe JSON response and applies the new values in place.
        $this->setProgress($I, 'database', 'finalizing', 'working', 90);
        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame([
            'state',
            'lifecycleLabel',
            'typeLabel',
            'stageLabel',
            'message',
            'progress',
        ], array_keys($snapshot));
        $I->assertSame('active', $snapshot['state']);
        $I->assertSame('Finalizing update…', $snapshot['stageLabel']);
        $I->assertSame('', $snapshot['message']);
        $I->assertSame(90, $snapshot['progress']);
        $I->assertStringNotContainsString('serialisedArray', $I->grabPageSource());

        // A normal full-page reload uses the same current snapshot.
        $I->amOnModulePage('System Admin', 'update.php');
        $I->see('Finalizing update…', '#updateProgress');
    }

    public function zeroPaddedGibbonLogIDIsAcceptedAndNormalized(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'database', 'installing', 'working', 70);
        $gibbonLogID = $this->createGibbonLog($I);
        $zeroPaddedGibbonLogID = str_pad((string) $gibbonLogID, 16, '0', STR_PAD_LEFT);

        $I->loginAsAdmin();
        $snapshot = $this->getProgressSnapshot($I, $zeroPaddedGibbonLogID);
        $I->assertSame('active', $snapshot['state']);
        $I->assertSame('Running', $snapshot['lifecycleLabel']);
        $I->assertSame('Installing update…', $snapshot['stageLabel']);
    }

    public function completionRefreshesAndTheReloadedPageUsesCurrentVersionData(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'file', 'finalizing', 'complete', 100);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('complete', $snapshot['state']);
        $I->assertSame('Complete', $snapshot['lifecycleLabel']);
        $I->assertSame(100, $snapshot['progress']);

        // PhpBrowser does not execute Alpine. Request the document that the
        // component reloads after a complete response and check normal overview data.
        $I->amOnModulePage('System Admin', 'update.php');
        $I->seeElement('#updateProgress[x-cloak][style="display: none;"]');
        $I->see('Codebase Version');
        $I->see($this->currentVersion());
    }

    public function failedInvalidAndNonUpdateRecordsReturnOnlySafeJson(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'file', 'installing', 'failed', 70);
        $gibbonLogID = $this->createGibbonLog($I);
        $otherGibbonLogID = $this->createGibbonLog($I, self::OTHER_PROCESS_TITLE);

        $I->loginAsAdmin();
        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('failed', $snapshot['state']);
        $I->assertSame('Failed', $snapshot['lifecycleLabel']);
        $I->assertSame('Installing update…', $snapshot['stageLabel']);
        $I->assertSame('Update failed. Check the server logs and try again, or update manually.', $snapshot['message']);
        $I->assertSame(70, $snapshot['progress']);

        foreach (['0', '0000000000000000', '0000000000000596invalid', '999999999', (string) $otherGibbonLogID] as $invalidID) {
            $snapshot = $this->getProgressSnapshot($I, $invalidID);
            $I->assertSame('unavailable', $snapshot['state']);
            $I->assertSame('Unavailable', $snapshot['lifecycleLabel']);
            $I->assertNull($snapshot['progress']);
        }
    }

    public function updateThatStoppedReportingIsReclaimedAsFailed(AcceptanceTester $I): void
    {
        // A process killed mid-update leaves a "working" snapshot behind. Its heartbeat
        // is hours old and no process log is active, so it must not lock the updater.
        $this->setProgress($I, 'file', 'installing', 'working', 40, time() - 7200);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('failed', $snapshot['state']);
        $I->assertSame('Installing update…', $snapshot['stageLabel']);

        // The update page no longer shows an active update, so actions are available again.
        $I->amOnModulePage('System Admin', 'update.php');
        $I->seeElement('#updateProgress[x-cloak][style="display: none;"]');
        $I->dontSee('Update in Progress');
    }

    public function snapshotWithoutAHeartbeatIsNotTreatedAsRunning(AcceptanceTester $I): void
    {
        // Snapshots written before heartbeats existed carry no timestamp.
        $this->setProgress($I, 'database', 'installing', 'working', 70, 0);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('failed', $snapshot['state']);

        $I->amOnModulePage('System Admin', 'update.php');
    }

    public function updateWithARecentHeartbeatStaysActive(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'file', 'downloading', 'working', 30, time() - 20);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('active', $snapshot['state']);

        $I->amOnModulePage('System Admin', 'update.php');
        $I->dontSeeElement('#updateProgress[style="display: none;"]');
    }

    public function updateActionsRejectRequestsThatCouldSkipTheCsrfCheck(AcceptanceTester $I): void
    {
        $actions = ['updateFilesProcess.php', 'updateProcess.php', 'updateRestoreProcess.php'];
        // Start from an idle snapshot. A leftover working snapshot with no live process is reclaimed
        // after 30 seconds, which would change the value this test compares against.
        $this->setProgress($I, 'file', 'preparing', 'idle', 0);
        $progressBefore = $I->grabFromDatabase('gibbonSetting', 'value', [
            'scope' => 'System Admin',
            'name' => 'backgroundUpdateProgress',
        ]);

        $I->loginAsAdmin();

        foreach ($actions as $action) {
            $url = '/modules/System Admin/'.$action;

            // A plain GET has no form fields at all.
            $I->amOnPage($url.'?type=regularRelease');
            $I->seeInCurrentUrl('return=error3');

            // A single field passes gibbon.php without a token check, so the action itself refuses it.
            $I->amOnModulePage('System Admin', 'update.php');
            $I->sendAjaxPostRequest($url, ['confirm' => 'Y']);
            $I->seeInCurrentUrl('return=error3');

            // The form fields without a token reach the shared CSRF check and are refused there.
            $I->amOnModulePage('System Admin', 'update.php');
            $I->sendAjaxPostRequest($url, ['confirm' => 'Y', 'address' => '/modules/System Admin/update.php', 'type' => 'regularRelease']);
            $I->seeInCurrentUrl('return=error9');
        }

        // None of the rejected requests started an update or changed the progress snapshot.
        $I->dontSeeInDatabase('gibbonLog', ['title' => self::SYSTEM_UPDATE_TITLE]);
        $I->seeInDatabase('gibbonSetting', [
            'scope' => 'System Admin',
            'name' => 'backgroundUpdateProgress',
            'value' => $progressBefore,
        ]);
    }

    public function authorizationAndDuplicateSubmissionSuppressionKeepRequestsSafe(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'database', 'preparing', 'working', 10);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsTeacher();
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$gibbonLogID);
        $I->see('Your request failed because you do not have access to this action.');

        $I->amOnPage('/');
        $I->click('Logout', 'a');
        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');

        // While an update is active, no background submit control is offered, so a second update cannot start
        $I->dontSeeElement('[hx-post$="Process.php"]');

        // A pending database update is shown as a disabled button while the update runs
        $databaseVersion = (string) $I->grabFromDatabase('gibbonSetting', 'value', [
            'scope' => 'System',
            'name' => 'version',
        ]);
        $processCount = $I->grabNumRecords('gibbonLog', [
            'title' => self::SYSTEM_UPDATE_TITLE,
        ]);
        if ($databaseVersion !== $this->currentVersion()) {
            $I->seeElement('button[name="Update in Progress"][disabled="disabled"]');
        }
        $I->assertSame($processCount, $I->grabNumRecords('gibbonLog', [
            'title' => self::SYSTEM_UPDATE_TITLE,
        ]));
        $I->seeInDatabase('gibbonLog', [
            'gibbonLogID' => $gibbonLogID,
            'title' => self::SYSTEM_UPDATE_TITLE,
        ]);
    }

    public function restoreSnapshotActivatesTheExistingProgressModal(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'restore', 'restoring', 'working', 70);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');
        $I->see('File system restore in progress.', '#updateProgress');
        $I->see('Restoring files…', '#updateProgress');
        $I->seeElement('#updateProgress', [
            'x-show' => 'updateProgressOpen',
            'role' => 'dialog',
        ]);

        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('active', $snapshot['state']);
        $I->assertSame('File system restore in progress.', $snapshot['typeLabel']);
        $I->assertSame('Restoring files…', $snapshot['stageLabel']);
        $I->assertSame(70, $snapshot['progress']);
    }

    public function updateAndRestoreFormSubmissionsUseBackgroundContracts(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');

        // The progress modal is rendered dormant, and a background submission starts it
        $I->seeElement('#updateProgressRegion');
        $I->seeElement('#updateProgress[style="display: none;"]');

        // Background controls appear only when there is a database update, a release to install,
        // or a backup to restore. When none applies, the page reports that the core is up to date.
        $source = $I->grabPageSource();
        if (preg_match('~hx-post="[^"]*/modules/System Admin/update(?:Files|Restore)?Process\.php"~', $source) === 1) {
            // A background submission posts with HTMX to a process script and leaves the page in place
            $I->seeElement('[hx-post$="Process.php"][hx-swap="none"]');
        }

        // The restore and InnoDB controls only appear in some states, so check them when present
        if (str_contains($source, 'updateRestoreProcess.php')) {
            $I->seeElement('[hx-post$="/updateRestoreProcess.php"]');
        }
        if (str_contains($source, 'updateProcess.php?type=InnoDB')) {
            $I->seeElement('form[action$="updateProcess.php?type=InnoDB"]');
        }
    }

    /**
     * The release number in version.php, so the assertion follows each new release.
     */
    private function currentVersion(): string
    {
        $version = '';
        include dirname(__DIR__, 3).'/version.php';

        return (string) $version;
    }
    /**
     * @return array{state: string, lifecycleLabel: string, typeLabel: string, stageLabel: string, message: string, progress: ?int}
     */
    private function getProgressSnapshot(AcceptanceTester $I, string $gibbonLogID): array
    {
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$gibbonLogID);
        $I->seeResponseCodeIsSuccessful();

        $snapshot = json_decode($I->grabPageSource(), true, 512, JSON_THROW_ON_ERROR);
        $I->assertIsArray($snapshot);

        return $snapshot;
    }

    /**
     * @param int|null $updated Heartbeat Unix time. Defaults to now; pass null-like 0 to omit it.
     */
    private function setProgress(AcceptanceTester $I, string $type, string $stage, string $status, int $progress, ?int $updated = null): void
    {
        $snapshot = [
            'type' => $type,
            'stage' => $stage,
            'status' => $status,
            'progress' => $progress,
        ];

        // Fixtures stand in for a live update, so they carry a fresh heartbeat
        // unless a test deliberately simulates an update that stopped reporting.
        if ($updated !== 0) {
            $snapshot['updated'] = $updated ?? time();
        }

        $I->updateFromDatabase('gibbonSetting', [
            'value' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        ], [
            'scope' => 'System Admin',
            'name' => 'backgroundUpdateProgress',
        ]);
    }

    private function createGibbonLog(AcceptanceTester $I, string $title = self::SYSTEM_UPDATE_TITLE): int
    {
        $personID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);
        $schoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID');

        // The endpoint uses only this record's ID and title. Fixtures deliberately
        // omit any serialized process payload.
        $gibbonLogID = $I->haveInDatabase('gibbonLog', [
            'gibbonPersonID' => $personID,
            'gibbonSchoolYearID' => $schoolYearID,
            'title' => $title,
        ]);

        $I->assertEmpty(
            $I->grabFromDatabase('gibbonLog', 'serialisedArray', ['gibbonLogID' => $gibbonLogID]),
            'The fixture must contain no serialized process data.'
        );

        return $gibbonLogID;
    }
}
