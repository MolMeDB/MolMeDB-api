# MolMeDB-api
MolMeDB is open-source...

## How to run
This repository is fully prepared for easy setup using Docker. Before starting, copy the appropriate docker-compose file as follows.

For local development:
```bash
cp docker-compose-development.yaml docker-compose.yaml
```
For production:
```bash
cp docker-compose-production.yaml docker-compose.yaml
```
Never modify the default `docker-compose-*.yaml` files. Your local `docker-compose.yaml` is added to .gitignore and will not be indexed by git.


### Setting up the .env file
First, create the .env file to set all the necessary values.
```bash
cp .env.example .env
```
The file is mostly prepared for local development, but some values still need to be checked. First, install the necessary libraries for developing the Laravel application on your machine by following the official documentation - https://laravel.com/docs/12.x/installation.

Then, run the following command:
```bash
php artisan key:generate
```
This will generate and fill the `APP_KEY` in the `.env` file. If you want to send emails from your environment, make sure to configure the `MAIL_*` variables. Otherwise, email sending will be replaced by log messages only in your console. Further configuration of other variables is outlined in the following sections.


### Development
First, prepare the `docker-compose.yaml` file as mentioned earlier:
```bash
cp docker-compose-development.yaml docker-compose.yaml
```
Since development containers are usually slower than running directly on your local machine, I recommend commenting out the web and app containers in your docker-compose.yaml. This way, only the db and adminer containers will remain. These containers are used for setting up the PostgreSQL database, installing required packages, and running the Adminer application on port `8080`, which is a replacement for PHPMyAdmin for PostgreSQL databases. To build and start the containers, run the following command:

```bash
sudo docker compose up
```

If you don’t want to monitor the logs, you can add the -d flag to run in the background:

```bash
sudo docker compose up -d
```

However, I recommend running the first command at least the first time in case any errors occur. If you’ve commented out the app and web sections, you will need to run your local server via Composer. Otherwise, your application will already be available at http://localhost:9080.


#### Running with composer
First, check your .env file, specifically the DB_HOST variable, which must be set to
```bash
DB_HOST=127.0.0.1
```
If you are developing locally and have commented out the app section in docker-compose.yaml, don’t forget to change the APP_URL value to
```bash
APP_URL=http://localhost:8000
```
For safety, clear the configuration cache
```bash
php artisan config:clear && php artisan cache:clear
```
To verify the database connection, try running the migration command to populate the database structure
```bash
php artisan migrate
```

If everything went well, Adminer will be available at http://localhost:8080. Log in using the credentials from the .env file. Make sure to select PostgreSQL as the connection type and set the host to db. Use the values from `DB_USERNAME`, `DB_PASSWORD`, and `DB_DATABASE` for the username, password, and database name. After logging in, you should see the tables created by the migration.

For local development, you usually don’t need to work with real data. Therefore, seeders have been added to the application to populate the database with fake data for testing purposes. Note: These features are not available on the production server. To populate the database, run the following command:

```bash
php artisan db:seed
```
During development, it’s common to need to empty and reseed the database. This can be easily done with the following command:

```bash
php artisan migrate:refresh && php artisan db:seed
```
Now, you can start your application with the command
```bash
composer run dev
```

If everything went well, the application will be available at http://localhost:8000. The database seeding will create an admin user with the following credentials
- email: admin@molmedb.cz
- password: admin

And that’s it!

---


<!-- Po spuštění kontejneru je potřeba inicializovat databázi
```bash
docker exec -it molmedb-dev-app bash -c "php artisan migrate --force"
```

Dále nezapomeňte nainstalovat potřebné balíčky
```bash
docker exec -it molmedb-dev-app bash -c "composer install"
```

A poté buď nahrát zálohu, nebo naplnit databázi náhodnými daty pomocí
```bash
docker exec -it molmedb-dev-app bash -c "php artisan db:seed"
``` -->


<!-- ### Production
For production environment just use docker-compose.prod.yaml file with the following commands:
```bash
# At first, build app container
docker compose -f docker-compose.prod.yaml build app 

# Build other containers and run app
docker compose -f docker-compose.prod.yaml up -d
```

Aplikace je poté dostupná na portu 9080. Toto nastavení je možné změnit v docker-compose.prod.yaml souboru. Pokud vše proběhlo v pořádku, bude vytvořena prázdná databáze a po otevření aplikace se otevře přihlašovací stránka (např. na adrese localhost:9080). Pro přihlášení je nutné naplnit databázi daty. Pozor! Seedery pro generování náhodných dat nebudou fungovat, protože balíček Faker není součástí produkčního buildu.  -->




