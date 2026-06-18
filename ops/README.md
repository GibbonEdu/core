# Install Gibbon using Docker Desktop

## Overview

This guide describes how to install Gibbon on your computer using Docker Desktop. This is an option for potential users and developers who want to run Gibbon without manually installing and configuring PHP, Apache, and MySQL on their machine.

## Before you start

Make sure you have [Docker Desktop](https://www.docker.com/products/docker-desktop) installed and running on your computer.

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

* Best to Select your country, currency and timezone so values feel realistic.

7. Click **Submit** to create the admin account and complete the installation.

8. Go to the Gibbon login page at http://localhost:8080.

9. Log in with the admin account you just created with the username **admin** and password **Foobar888**. You will then see Staff Dashboard.


## Troubleshooting

If you run into difficulties, try restarting the installation process:

1. Stop the Docker containers
```bash
./up.sh down
```

2. Delete config.php
```bash
rm config.php
```

3. Delete the volume for the database container
```bash
docker volume rm gibbon_gibbon_db_data
```

4. Execute the `up.sh` script again to restart the installation process
```bash
./up.sh
```
