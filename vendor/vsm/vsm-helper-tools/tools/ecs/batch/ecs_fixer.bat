:: Run easy-coding-standard (ecs) via this batch file inside your IDE e.g. PhpStorm (Windows only)
:: Install inside PhpStorm the  "Batch Script Support" plugin
cd..
cd..
cd..
cd..
cd..
cd..
php vendor\bin\ecs check vendor/vsm/vsm-helper-tools/src --fix --config vendor/vsm/vsm-helper-tools/tools/ecs/config.php
php vendor\bin\ecs check vendor/vsm/vsm-helper-tools/contao --fix --config vendor/vsm/vsm-helper-tools/tools/ecs/config.php
php vendor\bin\ecs check vendor/vsm/vsm-helper-tools/config --fix --config vendor/vsm/vsm-helper-tools/tools/ecs/config.php
php vendor\bin\ecs check vendor/vsm/vsm-helper-tools/templates --fix --config vendor/vsm/vsm-helper-tools/tools/ecs/config.php
php vendor\bin\ecs check vendor/vsm/vsm-helper-tools/tests --fix --config vendor/vsm/vsm-helper-tools/tools/ecs/config.php
