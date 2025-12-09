cd /app/pokitdok/
curl https://phar.phpunit.de/phpunit-6.0.9.phar > phpunit-6.0.9.phar
chmod +x phpunit-6.0.9.phar
mv phpunit-6.0.9.phar /usr/local/bin/phpunit
phpunit src/PokitDok/Tests/
