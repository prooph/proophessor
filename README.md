# Proophessor 
Exploring prooph components

Welcome at prooph! We are developing and supporting CQRS and EventSourcing infrastructure for PHP 7.1+ environments.
prooph is NOT a framework. Instead we provide components which address individual topics.

## Documentation

Documentation is [in the docs tree](docs/), and can be compiled using [bookdown](http://bookdown.io) and [Docker](https://www.docker.com/).

```bash
$ docker run --rm -it -v $(pwd):/app prooph/composer:7.1
$ docker run -it --rm -e CSS_PRISM=ghcolors -v $(pwd):/app sandrokeil/bookdown:develop docs/bookdown.json
$ docker run -it --rm -p 8080:8080 -v $(pwd):/app php:7.1-cli php -S 0.0.0.0:8080 -t /app/docs/html
```

Then browse to [http://localhost:8080/](http://localhost:8080/)

## Remote Docs

We use the remote content feature of bookdown to pull docs from our prooph component repos into a single online documentation.
This means that if you want to work on the docs - fix spelling, add new pages, improve wording or correct some logical bugs - 
then take a look at the root [bookdown.json](docs/bookdown.json) to see where the docs are pulled from. Head over to the target
repository and apply your changes there. Send us a pull request and we manage the rest. Thank you for your help.

## Example Application

Try out [proophessor-do](https://github.com/prooph/proophessor-do) and [pick up a task](https://github.com/prooph/proophessor-do#learning-by-doing)!

## Support

- Ask questions on [prooph-users](https://groups.google.com/forum/?hl=de#!forum/prooph) mailing list.
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

Happy messaging!
