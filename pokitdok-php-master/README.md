
pokitdok-php
=============

PokitDok [Platform API][apisite] Client for PHP

## Installation
Simply add a dependency on pokitdok/pokitdok-php to your project's composer.json file if you use Composer to manage the dependencies of your project. Here is a minimal example of a composer.json:

```json
{
	"require": {
		"pokitdok/pokitdok-php": "*"
	}
}
```

## Tests
```
Obtain a Test account key and secret from https://platform.pokitdok.com and update src/PokitDok/Tests/PlatformClientTest.php with your POKITDOK_PLATFORM_API_CLIENT_ID and POKITDOK_PLATFORM_API_CLIENT_SECRET.
```
This library is tested within the [official Docker images](https://hub.docker.com/_/php/) for the following versions:

* 5.6
* 7.0
* 7.1

If you already have docker, you can run the tests yourself via docker by running the testing script included in this repository:

```
    $ sh run_tests_in_docker.sh

```

## Resources
* [Read the PokitDok API docs][apidocs]
* [View Source on GitHub][code]
* [Report Issues on GitHub][issues]

[apisite]: https://platform.pokitdok.com/
[apidocs]: https://platform.pokitdok.com/documentation/v4#/
[code]: https://github.com/PokitDok/pokitdok-php
[issues]: https://github.com/PokitDok/pokitdok-php/issues

## Quick Start

```php
# Initialize environment
composer init --require="pokitdok/pokitdok-php:*"
composer install

# Initialize the client
require_once('vendor/autoload.php');
use PokitDok\Platform\PlatformClient;
$client = new PokitDok\Platform\PlatformClient('your_client_id', 'your_client_secret');

# Retrieve cash price information by zip and CPT code
$client->pricesCash(array('cpt_code' => "87799", 'zip_code' => "32218"));

# Retrieve insurance price information by zip and CPT code
$client->pricesInsurance(array('cpt_code' => "87799", 'zip_code' => "32218"));

# Retrieve provider information by NPI
$client->providers('1467560003');

# Search providers by name (individuals)
$client->providers(array('first_name' => "Jerome", 'last_name' => "Aya-Ay"));

# Search providers by name (organizations)
$client->providers(array('organization_name' => "Qliance"));

# Search providers by location and/or specialty
$client->providers(array('zipcode' => "29307", 'radius' => "10mi"));
$client->providers(array('zipcode' => "29307", 'radius' => "10mi", 'specialty' => "RHEUMATOLOGY"));

# Submit an eligibility request
$client->eligibility(array(
    'member' => array(
        'id' => "W000000000",
        'birth_date' => "1970-01-01",
        'first_name' => "Jane",
        'last_name' => "Doe"
    ),
    'provider' => array(
        'npi' => "1467560003",
        'last_name' => "AYA-AY",
        'first_name' => "JEROME"
    ),
    'service_types' => array("health_benefit_plan_coverage"),
    'trading_partner_id' => "MOCKPAYER"
));

# Submit a claims request
$client->claims(array(
    'transaction_code' => "chargeable",
    'trading_partner_id' => "MOCKPAYER",
    'billing_provider' => array(
        'taxonomy_code' => "207Q00000X",
        'first_name' => "Jerome",
        'last_name' => "Aya-Ay",
        'npi' => "1467560003",
        'address' => array(
            'address_lines' => array(
                "8311 WARREN H ABERNATHY HWY"
            ),
            'city' => "SPARTANBURG",
            'state' => "SC",
            'zipcode' => "29301"
        ),
        'tax_id' => "123456789"
    ),
    'subscriber' => array(
        'first_name' => "Jane",
        'last_name' => "Doe",
        'member_id' => "W000000000",
        'address' => array(
            'address_lines' => array("123 N MAIN ST"),
            'city' => "SPARTANBURG",
            'state' => "SC",
            'zipcode' => "29301"
        ),
        'birth_date' => "1970-01-01",
        'gender' => "female"
    ),
    'claim' => array(
        'total_charge_amount' => 60.0,
        'service_lines' => array(
            array(
                'procedure_code' => "99213",
                'charge_amount' => 60.0,
                'unit_count' => 1.0,
                'diagnosis_codes' => array(
                    "487.1"
                ),
                'service_date' => "2014-06-01"
            )
        )
    )
));

# Check the status of a claim
$claims_status = $client->claimsStatus(array(
    "patient" => array(
       "birth_date" => "1970-01-01",
       "first_name" => "Jane",
       "last_name" => "Doe",
       "id" => "W000000000"
    ),
    "provider" => array(
       "first_name" => "JEROME",
       "last_name" => "AYA-AY",
       "npi" => "1467560003"
    ),
    "service_date" => "2014-01-01",
    "trading_partner_id" => "MOCKPAYER"
));


# Check on pending platform activities

# check on a specific activity
$client->activities('5362b5a064da150ef6f2526c');

# check on a batch of activities
$client->activities(array('parent_id' => "537cd4b240b35755f5128d5c"));

# retrieve an index of activities
$client->activities();


# Perform a general PokitDok Platform API request (use for new APIs not yet incorporated into the client)
$client->request(
    'POST',
    '/eligibility/',
    array(
        'member' => array(
            'id' => "W000000000",
            'birth_date' => "1970-01-01",
            'first_name' => "Jane",
            'last_name' => "Doe"
        ),
        'provider' => array(
            'npi' => "1467560003",
            'last_name' => "AYA-AY",
            'first_name' => "JEROME"
        ),
        'service_types' => array("health_benefit_plan_coverage"),
        'trading_partner_id' => "MOCKPAYER"
    ),
    "application/json"
);

```

## Check SSL protocol and cipher
```php
$client->request('GET', '/ssl/');
```

## License
Copyright (c) 2017 PokitDok Inc. See [LICENSE][] for details.

[license]: LICENSE.txt

