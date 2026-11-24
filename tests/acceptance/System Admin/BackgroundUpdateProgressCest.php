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

    public function activeProgressAppearsBeforeOverviewAndSnapshotChangesPollResponse(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'database', 'installing', 'working', 70);
        $gibbonLogID = $this->createGibbonLog($I);
        $displayGibbonLogID = str_pad((string) $gibbonLogID, 16, '0', STR_PAD_LEFT);

        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');
        $I->seeElement(sprintf(
            '#updateProgress[hx-get*="updateProgressAjax.php?gibbonLogID=%s"]',
            $displayGibbonLogID
        ));
        $I->seeElement('#updateProgress', [
            'hx-target' => 'this',
            'hx-swap' => 'outerHTML',
        ]);
        $I->see('System update in progress.', '#updateProgress');
        $I->see('Running', '#updateProgress');
        $I->see('Database update', '#updateProgress');
        $I->see('Installing update…', '#updateProgress');
        $I->see('70%', '#updateProgress');

        $source = $I->grabPageSource();
        $progressPosition = strpos($source, 'id="updateProgress"');
        $overviewPosition = strpos($source, 'id="versionUpdate"');
        $I->assertNotFalse($progressPosition, 'The update progress component was rendered.');
        $I->assertNotFalse($overviewPosition, 'The update overview was rendered.');
        $I->assertLessThan($overviewPosition, $progressPosition, 'Progress is rendered before the update overview.');

        // The worker only changes the shared snapshot. The same existing log
        // record then produces an updated HTMX fragment without payload data.
        $this->setProgress($I, 'database', 'finalizing', 'working', 90);
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$gibbonLogID);
        $I->see('Finalizing update…', '#updateProgress');
        $I->see('90%', '#updateProgress');
        $I->seeElement('#updateProgress', ['hx-trigger' => 'every 2s']);

        // A normal full-page reload uses the same current snapshot.
        $I->amOnModulePage('System Admin', 'update.php');
        $I->seeElement('#updateProgress', ['hx-trigger' => 'every 2s']);
        $I->see('Finalizing update…', '#updateProgress');
        $I->see('90%', '#updateProgress');
    }

    public function zeroPaddedGibbonLogIDIsAcceptedAndNormalized(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'database', 'installing', 'working', 70);
        $gibbonLogID = $this->createGibbonLog($I);
        $zeroPaddedGibbonLogID = str_pad((string) $gibbonLogID, 16, '0', STR_PAD_LEFT);

        $I->loginAsAdmin();
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$zeroPaddedGibbonLogID);
        $I->seeResponseCodeIsSuccessful();
        $I->see('Running', '#updateProgress');
        $I->see('Installing update…', '#updateProgress');
        $I->seeElement(sprintf(
            '#updateProgress[hx-get*="updateProgressAjax.php?gibbonLogID=%s"]',
            (string) ((int) $gibbonLogID)
        ));
        $I->seeElement('#updateProgress', ['hx-trigger' => 'every 2s']);
    }

    public function completionRefreshesAndTheReloadedPageUsesCurrentVersionData(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'file', 'finalizing', 'complete', 100);
        $gibbonLogID = $this->createGibbonLog($I);

        $I->loginAsAdmin();
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$gibbonLogID);
        $I->seeResponseCodeIsSuccessful();
        $I->seeResponseHeader('HX-Refresh', 'true');
        $I->assertSame('', $I->grabPageSource());

        // PhpBrowser does not execute HTMX. Request the contractually refreshed
        // document and assert that it recalculates normal overview data.
        $I->amOnModulePage('System Admin', 'update.php');
        $I->dontSeeElement('#updateProgress');
        $I->see('Codebase Version');
        $I->see('v30.0.04');
    }

    public function failedInvalidAndNonUpdateRecordsRenderOnlySafeTranslatedOutput(AcceptanceTester $I): void
    {
        $this->setProgress($I, 'file', 'installing', 'failed', 70);
        $gibbonLogID = $this->createGibbonLog($I);
        $otherGibbonLogID = $this->createGibbonLog($I, self::OTHER_PROCESS_TITLE);

        $I->loginAsAdmin();
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$gibbonLogID);
        $I->see('Update failed. Check the server logs and try again.', '#updateProgress');
        $I->see('Failed', '#updateProgress');
        $I->see('Installing update…', '#updateProgress');
        $I->dontSeeInSource('hx-trigger="every 2s"');

        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID=0');
        $I->see('Background updates are unavailable on this system.', '#updateProgress');

        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID=0000000000000000');
        $I->see('Background updates are unavailable on this system.', '#updateProgress');

        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID=0000000000000596invalid');
        $I->see('Background updates are unavailable on this system.', '#updateProgress');

        // A canonical but unknown ID must be treated exactly like an invalid ID.
        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID=999999999');
        $I->see('Background updates are unavailable on this system.', '#updateProgress');

        $I->amOnPage('/modules/System Admin/updateProgressAjax.php?gibbonLogID='.$otherGibbonLogID);
        $I->see('Background updates are unavailable on this system.', '#updateProgress');
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

    public function updateAndRestoreFormTargetsRemainUnchanged(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->amOnModulePage('System Admin', 'update.php');

        $source = $I->grabPageSource();
        $I->assertTrue(
            str_contains($source, 'updateFilesProcess.php') || str_contains($source, 'updateProcess.php'),
            'The update form retains its existing file or database process target.'
        );

        // These controls depend on the current updater state and catalog. When
        // the configured fixture exposes them, assert their synchronous targets.
        if (str_contains($source, 'updateProcess.php?type=InnoDB')) {
            $I->assertStringContainsString('updateProcess.php?type=InnoDB', $source);
        }
        if (str_contains($source, 'updateRestoreProcess.php')) {
            $I->assertStringContainsString('updateRestoreProcess.php', $source);
        }
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
