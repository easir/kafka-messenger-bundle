# Kafka Messenger Bundle

Symfony bundle which provides Kafka Symfony Messenger Transport which allows
to decode messages including only event payload in the body based on mapping of
topic name and message class name.

The bundle is highly inspired by work of [koco/messenger-kafka](https://github.com/KonstantinCodes/messenger-kafka).

## Status

This package is currently in the active development.

## Requirements

* [PHP 7.2](http://php.net/releases/7_2_0.php) or greater
* [Symfony 4.4](https://symfony.com/roadmap/4.4) or [Symfony 5.x](https://symfony.com/roadmap/5.0)

## Configuration

### DSN

Specify a DSN starting with either `kafka://` or  `kafka+ssl://`.
There can be multiple brokers separated by `,`

* `kafka://my-local-kafka:9092`
* `kafka+ssl://my-staging-kafka:9093`
* `kafka+ssl://prod-kafka-01:9093,kafka+ssl://prod-kafka-01:9093,kafka+ssl://prod-kafka-01:9093`

### Example

The configuration options for `conf` can be found 
[here](https://github.com/edenhill/librdkafka/blob/master/CONFIGURATION.md).
It is highly recommended to set `enable.auto.offset.store` to `false` for consumers. 
Otherwise every message is acknowledged, regardless of any error thrown by the message handlers.


```
framework:
    messenger:
        transports:
            consumer:
                dsn: '%env(KAFKA_URL)%'
                options:
                    commitAsync: true
                    receiveTimeout: 10000
                    topics:
                        account.created: App\Account\Domain\Event\AccountWasCreated
                    conf:
                        enable.auto.offset.store: 'false'
                        group.id: 'my-group-id' # should be unique per consumer
                        security.protocol: 'sasl_ssl'
                        ssl.ca.location: '%kernel.project_dir%/config/kafka/ca.pem'
                        sasl.username: '%env(KAFKA_SASL_USERNAME)%'
                        sasl.password: '%env(KAFKA_SASL_PASSWORD)%'
                        sasl.mechanisms: 'SCRAM-SHA-256'
                        max.poll.interval.ms: '45000'
```

## Installation

This bundle is being served only from private repository therefore it is required 
to add proper repository into your `composer.json` file.

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/easir/kafka-messenger-bundle.git"
        }
    ]
}
```

Require the bundle implementation with Composer:

```sh
composer require easir/kafka-messenger-bundle
```

To enable the Bundle manually add it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
return [
    // ...
    Easir\KafkaMessengerBundle\KafkaMessengerBundle::class => ['all' => true],
];
```

### Code Style

This bundle enforces the [Easir Code Standards](https://github.com/easir/coding-standard) 
during development using the [PHP CS Fixer](https://cs.sensiolabs.org/) utility. 
Before committing any code, you can run the utility so it can fix any potential rule 
violations for you:

```sh
vendor/bin/phpcs
```

## Reporting issues

Use the [issue tracker](https://github.com/easir/kafka-messenger-bundle/issues) 
to report any issues you might have.

## License

See the [LICENSE](LICENSE.md) file for license rights and limitations (MIT).
