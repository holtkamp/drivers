# Bernard Drivers

[![Latest Version](https://img.shields.io/github/release/bernardphp/drivers.svg?style=flat-square)](https://github.com/bernardphp/drivers/releases)
[![Build Status](https://img.shields.io/travis/bernardphp/drivers.svg?style=flat-square)](https://travis-ci.org/bernardphp/drivers)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/bernardphp/drivers.svg?style=flat-square)](https://scrutinizer-ci.com/g/bernardphp/drivers)
[![Quality Score](https://img.shields.io/scrutinizer/g/bernardphp/drivers.svg?style=flat-square)](https://scrutinizer-ci.com/g/bernardphp/drivers)
[![Total Downloads](https://img.shields.io/packagist/dt/bernard/drivers.svg?style=flat-square)](https://packagist.org/packages/bernard/drivers)

**Official Bernard drivers.**


## Install

Via Composer

```bash
$ composer require bernard/drivers
```


## Drivers

- [Amazon SQS](src/Sqs)
- [AMQP](src/Amqp)
- [Iron MQ](src/IronMQ)
- [Pheanstalk](src/Pheanstalk)
- [Predis](src/Predis)
- [Queue Interop](src/QueueInterop)
- [Redis](src/Redis)


## Testing

### Running tests locally

Build the provided `Dockerfile`:

```bash
$ docker build -t bernardphp .
```

Start the services using Docker Compose:

```bash
$ docker-compose up -d
```

Wait for them to start. Then execute the test suites:

```bash
$ docker run --rm -it -v $PWD:/app -w /app --network drivers_default bernard vendor/bin/phpunit
$ docker run --rm -it -v $PWD:/app -w /app --network drivers_default bernard vendor/bin/phpunit --group integration
```

`drivers_default` is the network name created by Docker Compose.


## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
