# NewsBite API - A Laravel App
Welcome to the take-home challenge for the FullStack web developer position. The challenge is to build a news aggregator website that pulls articles from various sources and displays them in a clean, easy-to-read format.

## Required Versions
- PHP v8.2
- Laravel v10.0

## Running the App

To run the app without Docker, follow these steps:

1. Clone this repository to your local machine.
2. Install the necessary dependencies using Composer:

### Without Docker

To run the app without Docker, follow these steps:


```shell
   composer install

```
2. Create a copy of the `.env.example` file and rename it to `.env`. Configure your database settings in the `.env` file.

3. Generate an application key:

```shell
    php artisan key:generate
```
4. Migrate Database:

```shell
    php artisan migrate
```

5. Execute Seeder:

```shell
    php artisan db:seed
```

6. Execute News Scrappers: Laravel Command schedulars to get the news data, filter out and store in DB:

#### TO Manually Execute Scrapper

```shell
    php artisan schedule:run
```

OR to execute a specific scrapper

```shell
    php artisan app:guardian-api-scraper
```

7. Start the development server:

```shell
    php artisan serve
```

### With Docker

To run the app with Docker, ensure you have Docker installed on your machine, and then follow these steps:

1. Clone this repository to your local machine.
2. Open your terminal and navigate to the project directory.
3. Build the Docker image using the provided Dockerfile:

```shell
   sudo docker-compose --build
   docker-compose up -d
```
4. This will start the Laravel app in a Docker container, and you can access it in your web browser at [`http://localhost:8000`].

5. TO run the migrations, seeders and Schedulers, access that container:

```shell
    docker exec -it <container-id> php artisan migrate
    docker exec -it <container-id> php artisan db:seed
    docker exec -it <container-id> php artisan schedule:run
```

### Setup Cronjob

1. To set up the scheduler with a cron job, you can add the following entry to your server's crontab:

```shell
   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1 > /etc/crontabs/www-data
```
