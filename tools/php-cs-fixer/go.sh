#!/bin/sh

# php-cs-fixer incompatible avec PHP8.0 au 15/05/2021

# mkdir -p tools/php-cs-fixer
# composer require --working-dir=tools/php-cs-fixer friendsofphp/php-cs-fixer
# composer install

tools/php-cs-fixer/vendor/bin/php-cs-fixer fix core
tools/php-cs-fixer/vendor/bin/php-cs-fixer fix themes/defaut
for f in $(ls *.php); do tools/php-cs-fixer/vendor/bin/php-cs-fixer fix $f; done
tools/php-cs-fixer/vendor/bin/php-cs-fixer fix update

