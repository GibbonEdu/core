# Install Gibbon using Docker Desktop

## Overview

This guide describes how to install Gibbon on your computer using Docker Desktop. This is an option for potential users and developers who want to run Gibbon without manually installing and configuring PHP, Apache, and MySQL on their machine.

On successfully completing the installation, a Gibbon instance with its installer tool will be accessible at http://localhost:8080.

## Before you start

- Make sure you have [Docker Desktop](https://www.docker.com/products/docker-desktop) installed and running on your computer.
- No local PHP, Apache, or MySQL is required: everything runs inside the containers.
- All ports are bound to localhost: the stack is not reachable from other machines on your network.
- **Windows:** run the scripts from **Git Bash** (installed with [Git for Windows](https://gitforwindows.org/)) or from **WSL2** — PowerShell and CMD cannot run shell scripts. With WSL2, clone the repository into the Linux filesystem (e.g. `~/gibbon`) rather than `/mnt/c/...`, as cross-filesystem bind mounts are much slower.

## Steps

1. Execute the `up.sh` script to build and run the application and database containers:
```bash
./up.sh
```

2. Go to http://localhost:8080 on a browser to access Gibbon installer tool.

3. Scroll to the bottom of the INSTALLATION - STEP 1 page and click **Submit**.

4. On the INSTALLATION - STEP 2 page, enter the following mandatory details:

* Database Server: gibbon_db
* Database Name: gibbon
* Database User: gibbon
* Database Password: change_me
* Install Demo Data?: Yes

5. Click **Submit** to create the database and populate it with demo data.

6. On the INSTALLATION - STEP 3 page, enter the following mandatory details:

User Account

* Surname: Bar
* First Name: Foo
* Email: admin@school.com
* Username: admin
* Password: Foobar888
* Confirm Password: Foobar888

Organisation Settings

* Organisation Name: Gibbon School
* Organization Initials: GS

Miscellaneous

* Select your actual country, currency and timezone so values feel realistic.

7. Click **Submit** to create the admin account and complete the installation.

8. Go to the Gibbon login page at http://localhost:8080. Log in with the admin account you just created with the username **admin** and password **Foobar888**. You will then see Staff Dashboard.

## Where to go next

Try installing example data to explore Gibbon with more realistic data. See [Install example data for Gibbon](INSTALL_DATA.md).

To run unit, installer, and acceptance tests in this Docker environment, see [tests/README.md](../../tests/README.md).


## Optional: catching outgoing mail

Add `COMPOSE_PROFILES=mail` to `.env` and run `./up.sh` to start a [Mailpit](https://mailpit.axllent.org/) mail catcher, then set the SMTP host to `mail` and port `1025` in Gibbon's email settings. Outgoing mail is captured and shown at http://localhost:8025 instead of being sent.

## Troubleshooting

### `env: can't execute 'bash\r': No such file or directory`

The scripts were checked out with Windows (CRLF) line endings. The repository forces LF for all container-consumed files via `.gitattributes`, but an existing clone keeps its CRLF copies until those files are checked out again. After pulling the latest changes:

```bash
# Delete the affected files, then restore them from the repository
# (`git checkout -- .` re-creates missing files from the index, now with LF)
rm up.sh setup_devdb.sh tests/*.sh resources/ops/scripts/*.sh
git checkout -- .
```

### `MySQL did not become ready. Run ./up.sh first.`

The first start initializes the database volume, which is slow on some machines. Retry the script, or allow a longer wait:

```bash
MYSQL_WAIT_SECONDS=300 ./setup_devdb.sh
```

### Older errors from an outdated clone

`The process "'/usr/bin/unzip' ..." exceeded the timeout of 300 seconds` and `exec: C:/Program Files/Git/var/www/html/...: not found` were fixed in the app image and the scripts. Pull the latest changes and run `./up.sh` again — it rebuilds the app image.

### `Bind for 0.0.0.0:3306 failed: port is already allocated` (or port 8080)

Another service on your machine is already using the port (often a local MySQL on 3306). Set `DB_PORT` and/or `APP_PORT` in `.env`, update `ABSOLUTE_URL` to match `APP_PORT`, then run `./up.sh` again.

### Resetting the installation

If you run into other difficulties, try restarting the installation process:

1. Stop containers and delete the database volume
```bash
./up.sh down
```

2. Delete `config.php`
```bash
rm config.php
```

3. Execute `up.sh` script again to restart installation process
```bash
./up.sh
```
