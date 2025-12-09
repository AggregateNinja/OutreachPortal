cd /app/pokitdok/
curl https://phar.phpunit.de/phpunit-5.7.16.phar > phpunit-5.7.16.phar
chmod +x phpunit-5.7.16.phar
mv phpunit-5.7.16.phar /usr/local/bin/phpunit
phpunit src/PokitDok/Tests/
