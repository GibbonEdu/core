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

