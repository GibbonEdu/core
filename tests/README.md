# How to run tests

Developer Docker only. Start the stack first, then use the scripts below. They call the existing Codeception suites (`unit`, `install`, `acceptance`) inside the `test` container.

```bash
./up.sh
```

## Unit tests

```bash
tests/run_unit.sh
tests/run_unit.sh unit Data
tests/run_unit.sh unit Data/ValidatorTest.php
```

## Installer test

This deletes `config.php` and recreates the database.

```bash
tests/run_install.sh
tests/run_install.sh --debug -vvv install
```

## Acceptance tests

Load demo data first, then run the suite. The script temporarily sets `absoluteURL` to `TEST_ABSOLUTE_URL` (`http://gibbon.test`) and restores `ABSOLUTE_URL` when it finishes.

```bash
./setup_devdb.sh
tests/run_acceptance.sh
tests/run_acceptance.sh acceptance Markbook
tests/run_acceptance.sh acceptance --debug 'Calendar/CalendarEventManageCept.php'
```

For debugging a failed acceptance test, you can add `skip_cleanup_if_failed: true` under the `Db` module in `acceptance.suite.yml` so the database is not reset.
