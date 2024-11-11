# Welcome to A2A API!

Thank you for the opportunity to create this coding challenge API which provides the following functionality:

> Pretend you are assisting a movie research company in analyzing trends in the industry. The company wants to better understand box office performance. Your assignment is to write some code to gather the information they need.

## Setting up your environment

The API was built using Laravel 8 running locally on Mac OSX, Apache 2 and PHP 8.

1. Make sure composer is installed and run `composer install` in the project folder.
2. Create mysql database and call it a2a.
3. Add .env file based on .env.example.  You will need to adjust database connection values for your mysql.
4. Run the migration `php artisan migrate` to create tables.
5. Seed the users table by running `php artisan db:seed --class=UserSeeder` which will create a random user with password Testing123#
6. Seed the theaters, movies and movie_sales tables by running `php artisan db:seed --class=MovieSalesSeeder` 

## Postman Setup

Import the following environment and collection files into postman.

`a2a-api.postman_environment.json` contains the environment variables used in the collection.
`a2a.postman_collection.json` contains the collection of endpoints.

Edit the environment to ensure the url matches your local configuration. All endpoints except Login requires authentication.  Login using the email from the user that was created when you seed the users table with password Testing123#.  Upon login, the `sid` will be created an used throughout the api automatically.

> API documentation: https://documenter.getpostman.com/view/696592/Uz5NiD3M

