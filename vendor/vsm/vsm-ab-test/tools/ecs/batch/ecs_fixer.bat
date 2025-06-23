:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/caeli-wind/caeli-ab-test/src --fix --config vendor/caeli-wind/caeli-ab-test/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-ab-test/contao --fix --config vendor/caeli-wind/caeli-ab-test/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-ab-test/config --fix --config vendor/caeli-wind/caeli-ab-test/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-ab-test/templates --fix --config vendor/caeli-wind/caeli-ab-test/tools/ecs/config.php
php vendor\bin\ecs check vendor/caeli-wind/caeli-ab-test/tests --fix --config vendor/caeli-wind/caeli-ab-test/tools/ecs/config.php
