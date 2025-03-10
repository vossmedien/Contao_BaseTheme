:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/caeli-wind/contao-caeli-content-creator/src --fix --config vendor/caeli-wind/contao-caeli-content-creator/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/contao-caeli-content-creator/contao --fix --config vendor/caeli-wind/contao-caeli-content-creator/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/contao-caeli-content-creator/config --fix --config vendor/caeli-wind/contao-caeli-content-creator/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/contao-caeli-content-creator/templates --fix --config vendor/caeli-wind/contao-caeli-content-creator/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/contao-caeli-content-creator/tests --fix --config vendor/caeli-wind/contao-caeli-content-creator/tools/ecs/config.php
