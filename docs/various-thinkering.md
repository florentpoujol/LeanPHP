# If a framework like Laravel/Symfony brings too much complexity, what is a better way ?

A better, simpler way would be to have two things :
- a small library that brings components, common features that you need while building an app, with a simple, small, straightforward implementation that is enough for 80 to 90% of projects
- a light glue/framework that can help bring some of these components together to help build a project with it

The key points of the system would be:
- not a framework, but we bring you a library and we show you how to build a project with it
- as few production dependencies as possible
- a simple implementation (PSR compatible when applicable ?) that is enough for most, but the documentation show you how to use other most full featured library (usually a Symfony component)
- modern, strict PHP
- one way to do each things

Question : do we care about PSR compatibility ? 
If we provide convenience methods in the same class, this seems pointless.
But we could provide classes that decorates PSR implementations.
I think Symfony stuff are usually not PSR compliant but they provide "bridges" when needing to convert from one to the other.


--------------------------------------------------

## Components / domains

For each component, truly evaluate the differences between our custom solution and the Symfony one.
Is ours really much smaller,  and easier ? 
Does it really provide 80% of the features.


### Dependency injection:

- custom (depends on how can we make stuff different from symfony (how to autowire value from config or any place for instance))
- or use symfony container, or PHP-DI
	- that depends who we can use it without "compilation"
- a lot of the complexities like autowiring scalar, or tagged implementation can be circumvented by doing this stuff manually in factories or with more interfaces. Same thing for changing implementation during tests for instance


### SQL

- a classic migration system with classes with up/down method, that run raw SQL (so no Doctrine DBAL, no automatic migrations and no Laravel like migration builder)
- fixture system 
	- that allow to seed raw SQL
	- should be able to directly seed entities
	- entities with fromDatabaqse() / toDatabase() system (either from the same class or another)
- a simple query builder (which allow to compose a request by passing it around)
- a way to do raw queries (directly PDO, or always the query builder ?)
- we must have a way to put raw query result into entities, including results that contain two different entities at once
- show how to do both repositories with small entities, and active record


### Http routing

- custom (fast enough ? > build benchmark for about 100 routes)
- fastroute
- Symfony router


### HTTP objects

- both PSR compliant and with convenience methods ? Just convenience methods over PSR7
- just nyholm/ps7


### CLI commands

- custom
- Symfony Console
- other console framework (mnapoli/silly > based on symfony console)


### Event + command bus + queue system

- a custom message bus + transports, maybe do something separate for events

message are a data class, which can also have behavior
handlers receive the data and do something with it

messages can be given to a transport first to become async message
they will be picked up by a worker and then sent to the bus

you can configure transport (if any) via attributes on the data class 

le nom des transport est une interface, qui doit être liée à une implémentation de transport
si besoin de metadata pour les job, la classes de data peut être nestée dans une classe de job, qui est celle qui a les infos comme delai, retry logic, etc...

class MyData {}

#[Handles(MyData::class)]
class MyHandler {}

class MyTransport implements

// do that via metadata, but Autodiscoverer to setup (and cache)
$bus->setHandlers([
	MyData => [
		Myhandler,
	],
]);
$bus->setTransports([
	MyData => [
		MyTransport::class, // if this is an interface, how to change the transport during test
	],
]);

$bus->dispatch(MyData);

$job = new Job(MyData, delayInSeconds: 5);

$bus->dispatch($job); // the handler receive both the dataclass and then the job, if there is a job argument


### Cache

- PSR interface, with a few implementation (Redis + memcache + PDO)


### Views

- no custom view, PHP is already a template engine
- show how to use Twig


### Http Client

- custom, simple CURL-based (1.1 only)
- Symfony


### Validator

- show how to use it to validate an entity, as well as incoming HTTP requests
- Symfony validator


### Encrypter

- custom


### Filesystem

- custom local + s3 + FTP ?


### Logging

- custom (file + resource-based)
- defer everything else to Monolog


### Email

- custom SMTP + an example basic client for a Saas Service like SendGrid
- SMTP is hard, see Symfony Mailer, which also has many DSK for SaaS


### CSVReader

- ??


### DateTime

- don't provide anything, refer to Carbon


### Translations

- custom, with placeholder, and pluralizer


--------------------------------------------------

## Project specific stuffs

### Config and environment

- classic array based
- class-based that are autowirable


### Something to do classic login

- custom, password based, cookie based, with email validation and remember me


### Scheduler system

- run callable with cron expressions
- https://github.com/dragonmantank/cron-expression


### Starter project

- provide a starter project with a classic structure, default files, including DX tools like PHPStan and PHP CS Fixer, but still say in the doc that whatever, you do you


### Debug

- custom Solution to observe the SQL queries, the HTTP requests, the events, ...
- We must have something akin to Laravel Telescope or the debugbar/web profiler.
- offload as many things as possible to Symfony Debug / Var Dumper


### Test facilities

- similar to KernelTestCase / WebTestcase


### Basic CLI

For running commands like the migrations, the seeders > or provide that with the SQL domain ?



----------------------------------

## TODO

- ConfigRepository : add dotted key support
- Container: autowire parameter that have a different name with an attribute
- PhpViewRenderer: add layouts, easy escaping
- seeder command: allow to have files in env-named folder ?
- entity hydrator: make it recursive, have a default interface>implementation for common data interfaces like DateTimeInterface, allow to call the constructor ?