:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/caeli-wind/caeli-hubspot-connect/src --fix --config vendor/caeli-wind/caeli-hubspot-connect/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-hubspot-connect/contao --fix --config vendor/caeli-wind/caeli-hubspot-connect/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-hubspot-connect/config --fix --config vendor/caeli-wind/caeli-hubspot-connect/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-hubspot-connect/templates --fix --config vendor/caeli-wind/caeli-hubspot-connect/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-hubspot-connect/tests --fix --config vendor/caeli-wind/caeli-hubspot-connect/tools/ecs/config.php
