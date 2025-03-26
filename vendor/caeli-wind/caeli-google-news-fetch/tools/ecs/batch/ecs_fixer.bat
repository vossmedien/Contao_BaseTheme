:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/caeli-wind/caeli-google-news-fetch/src --fix --config vendor/caeli-wind/caeli-google-news-fetch/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-google-news-fetch/contao --fix --config vendor/caeli-wind/caeli-google-news-fetch/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-google-news-fetch/config --fix --config vendor/caeli-wind/caeli-google-news-fetch/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-google-news-fetch/templates --fix --config vendor/caeli-wind/caeli-google-news-fetch/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-google-news-fetch/tests --fix --config vendor/caeli-wind/caeli-google-news-fetch/tools/ecs/config.php
