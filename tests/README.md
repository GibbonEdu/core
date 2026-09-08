# How to run tests

## Run unit tests

Deploy a local instance of Gibbon using Docker:
```bash
./up.sh
```

Execute all unit tests:
```bash
tests/test_unit.sh 
```

Execute unit tests for a specific module:
```bash
tests/run_unit.sh tests/unit/Data
```

Execute specific unit test file:
```bash
tests/run_unit.sh tests/unit/Data/ValidatorTest.php
```


## Run installer test

Deploy a local instance of Gibbon using Docker:
```bash
./up.sh
```

Execute installer tests:
```bash
tests/run_install.sh
```

## Run acceptance tests

Deploy a local instance of Gibbon using Docker:
```bash
./up.sh
```

Install example data into the database:
```bash
./setup_db.sh
```

Execute all acceptance tests:
```bash
tests/test_acceptance.sh
```

