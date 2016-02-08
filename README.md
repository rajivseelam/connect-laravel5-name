## Installation

Add to dependencies

    "cartalyst/sentry": "2.1.*",
    "google/apiclient": "1.0.*@beta",
    "knplabs/github-api": "~1.2",
    "facebook/php-sdk-v4" : "4.0.*",
    "league/oauth2-client": "~0.3"


Add to providers

	'Rjvim\Connect\ConnectServiceProvider',


Make changes to *app/database.php*

Run: `php artisan migrate --package=cartalyst/sentry`

Run: `php artisan config:publish cartalyst/sentry`

Edit *app/config/packages/cartalyst/sentry/config.php* to

	'model' => 'Rjvim\Connect\Models\User',

Run: `php artisan migrate --package="rjvim/connect"`

Run: `php artisan config:publish rjvim/connect`
