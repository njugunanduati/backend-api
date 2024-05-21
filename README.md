## Backend Api

Laravel api to run the Profit Acceleration Software.

### `Installing`

After cloning the code from the repository, run this command:

* ` composer install` — for installing the composer dependancies required

### `Running`

After installing the application, run the following command ti start up the application.

* ` php -S localhost:9999 -t public`

### `Versioning`

Use these three npm commands that automatically increments the package version and creates a git commit along with a corresponding version tag.

* ` php artisan version:patch` — for releases with only bug fixes
* ` php artisan version:minor` — for releases with new features w/ or w/o bug fixes
* ` php artisan version:major` — for major releases or breaking features

Remember to push your commit with --tag attribute i.e `git push origin master --tags`
