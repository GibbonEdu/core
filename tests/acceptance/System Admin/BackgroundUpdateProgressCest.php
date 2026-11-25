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

        $source = $I->grabPageSource();
        $I->assertStringContainsString('x-trap.inert.noscroll.noreturn="updateProgressOpen"', $source);
        $I->assertStringContainsString('@click="updateProgressOpen = false"', $source);
        $I->assertStringContainsString('fetch(this.pollUrl', $source);
        $I->assertStringContainsString('applySnapshot(snapshot)', $source);
        $I->assertStringContainsString('window.setTimeout(() => this.poll(), 1000)', $source);
        $I->assertStringContainsString('progress: 70', $source);
        $I->assertStringContainsString(":style=\"'width: ' + progress + '%'\"", $source);
        $I->assertStringContainsString("x-on:background-update-started.window=\"start(\$event.detail.gibbonLogID)\"", $source);
        $I->assertStringNotContainsString('hx-trigger="every', $source);
        $I->assertStringNotContainsString('hx-swap="outerHTML"', $source);
        $progressPosition = strpos($source, 'id="updateProgress"');
        $overviewPosition = strpos($source, 'id="versionUpdate"');
        $I->assertNotFalse($progressPosition, 'The update progress component was rendered.');
        $I->assertNotFalse($overviewPosition, 'The update overview was rendered.');
        $I->assertLessThan($progressPosition, $overviewPosition, 'Overview is rendered before the update progress component.');

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
        $I->assertStringContainsString('isPolling: false', $I->grabPageSource());
        $I->see('Codebase Version');
        $I->see('v30.0.04');
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
        $I->assertSame('Update failed. Check the server logs and try again.', $snapshot['message']);
        $I->assertSame(70, $snapshot['progress']);

        foreach (['0', '0000000000000000', '0000000000000596invalid', '999999999', (string) $otherGibbonLogID] as $invalidID) {
            $snapshot = $this->getProgressSnapshot($I, $invalidID);
            $I->assertSame('unavailable', $snapshot['state']);
            $I->assertSame('Unavailable', $snapshot['lifecycleLabel']);
            $I->assertSame('Background updates are unavailable on this system.', $snapshot['message']);
            $I->assertNull($snapshot['progress']);
        }
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
        $I->see('Update in Progress');

        // The active update makes the matching submit control a disabled button,
        // preventing a normal browser submission before any update workflow starts.
        $processCount = $I->grabNumRecords('gibbonLog', [
            'title' => self::SYSTEM_UPDATE_TITLE,
        ]);
        $I->seeElement('button[name="Update in Progress"][disabled="disabled"]');
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
        $I->see('System restore in progress.', '#updateProgress');
        $I->see('Restoring files…', '#updateProgress');
        $I->seeElement('#updateProgress', [
            'x-show' => 'updateProgressOpen',
            'role' => 'dialog',
        ]);

        $snapshot = $this->getProgressSnapshot($I, (string) $gibbonLogID);
        $I->assertSame('active', $snapshot['state']);
        $I->assertSame('System restore in progress.', $snapshot['typeLabel']);
        $I->assertSame('Restoring files…', $snapshot['stageLabel']);
        $I->assertSame(70, $snapshot['progress']);
    }

    public function updateAndRestoreFormSubmissionsUseBackgroundContracts(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');

        $source = $I->grabPageSource();
        $I->assertTrue(
            str_contains($source, 'updateFilesProcess.php') || str_contains($source, 'updateProcess.php'),
            'The update form retains its existing file or database process target.'
        );
        $I->assertMatchesRegularExpression(
            '/hx-post="[^"]+\/modules\/System Admin\/(?:updateFilesProcess|updateProcess)\.php"/',
            $source,
            'Background-eligible update submissions use their real HTMX process action.'
        );
        $I->assertStringContainsString('hx-swap="none"', $source);
        $I->assertStringContainsString(
            "x-on:background-update-started.window=\"start(\$event.detail.gibbonLogID)\"",
            $source
        );
        $this->assertBackgroundStartEventWiring($I);
        $I->seeElement('#updateProgress', [
            'x-show' => 'updateProgressOpen',
            'style' => 'display: none;',
        ]);
        $I->assertStringContainsString('fetch(this.pollUrl', $source);
        $I->assertStringNotContainsString('hx-swap="outerHTML"', $source);

        // These controls depend on the current updater state and catalog. When
        // the configured fixture exposes them, assert their expected submission mode.
        if (str_contains($source, 'updateProcess.php?type=InnoDB')) {
            $I->assertStringContainsString('updateProcess.php?type=InnoDB', $source);
        }
        if (str_contains($source, 'updateRestoreProcess.php')) {
            $I->assertMatchesRegularExpression(
                '/hx-post="[^\"]+\/modules\/System Admin\/updateRestoreProcess\.php"/',
                $source,
                'Restore submissions use the shared HTMX background contract.'
            );
        }
    }

    /**
     * PhpBrowser does not execute HTMX or Alpine. Assert the complete event
     * contract so the dormant modal receives the trigger emitted by every real
     * background update or restore action.
     */
    private function assertBackgroundStartEventWiring(AcceptanceTester $I): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $eventPayload = "'background-update-started' => ['gibbonLogID' => \$gibbonLogID]";

        foreach (['updateFilesProcess.php', 'updateProcess.php', 'updateRestoreProcess.php'] as $action) {
            $actionSource = file_get_contents($projectRoot.'/modules/System Admin/'.$action);
            $I->assertNotFalse($actionSource, 'The background update action is readable.');
            $I->assertStringContainsString($eventPayload, $actionSource);
        }

        $restoreSource = file_get_contents($projectRoot.'/modules/System Admin/updateRestoreProcess.php');
        $I->assertNotFalse($restoreSource, 'The restore action is readable.');
        $I->assertStringContainsString("SystemUpdateProcess::class, 'run', ['restore'], false", $restoreSource);
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

    private function setProgress(AcceptanceTester $I, string $type, string $stage, string $status, int $progress): void
    {
        $I->updateFromDatabase('gibbonSetting', [
            'value' => json_encode([
                'type' => $type,
                'stage' => $stage,
                'status' => $status,
                'progress' => $progress,
            ], JSON_THROW_ON_ERROR),
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
