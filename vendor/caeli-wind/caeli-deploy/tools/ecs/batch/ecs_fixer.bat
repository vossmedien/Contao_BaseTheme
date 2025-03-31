:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/caeli-wind/caeli-deploy/src --fix --config vendor/caeli-wind/caeli-deploy/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-deploy/contao --fix --config vendor/caeli-wind/caeli-deploy/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-deploy/config --fix --config vendor/caeli-wind/caeli-deploy/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-deploy/templates --fix --config vendor/caeli-wind/caeli-deploy/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-deploy/tests --fix --config vendor/caeli-wind/caeli-deploy/tools/ecs/config.php
