# Proophessor 
Exploring prooph components

Welcome at prooph! We are developing and supporting CQRS and EventSourcing infrastructure for PHP 5.5+ environments.
Proophessor is NOT a framework. Instead we provide components which address individual topics.

Therefor, proophessor is only a meta package giving you an overview of the prooph ecosystem and acting as a starting point
for those who are new to the world of messaging and event sourced domain models.

*Note: Proophessor started as a Zend Framework 2 integration module for prooph components. But things have changed.
All prooph components now ship with `container-interop` compatible factories and therefor provide interoperable framework integration.
A module, bundle, bridge or whatever is no longer needed to integrate prooph in your web framework of choice.*

## Documentation

Documentation is [in the doc tree](docs/), and can be compiled using [bookdown](http://bookdown.io).

```console
$ php ./vendor/bin/bookdown docs/bookdown.json
$ php -S 0.0.0.0:8080 -t docs/html/
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

## Example Application

Try out [proophessor-do](https://github.com/prooph/proophessor-do) and [pick up a task](https://github.com/prooph/proophessor-do#learning-by-doing)!

## Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

Happy messaging!
