# Doctrine Repo Helper

[![Build Status](https://travis-ci.com/curtiskelsey/doctrine-repo-helper.svg?branch=master)](https://travis-ci.com/curtiskelsey/doctrine-repo-helper)
[![Latest Stable Version](https://poser.pugx.org/curtiskelsey/doctrine-repo-helper/v/stable)](https://packagist.org/packages/curtiskelsey/doctrine-repo-helper)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/curtiskelsey/doctrine-repo-helper/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/curtiskelsey/doctrine-repo-helper/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/curtiskelsey/doctrine-repo-helper/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/curtiskelsey/doctrine-repo-helper/?branch=master)

[![License](https://poser.pugx.org/curtiskelsey/doctrine-repo-helper/license)](https://packagist.org/packages/curtiskelsey/doctrine-repo-helper)
[![composer.lock](https://poser.pugx.org/curtiskelsey/doctrine-repo-helper/composerlock)](https://packagist.org/packages/curtiskelsey/doctrine-repo-helper)
[![Total Downloads](https://poser.pugx.org/curtiskelsey/doctrine-repo-helper/downloads)](https://packagist.org/packages/curtiskelsey/doctrine-repo-helper)

This package was created for developers using the `doctrine/doctrine-orm-module`
to provide a CLI command for generating custom repository getter methods.

## Introduction

Ever worked with Doctrine custom repositories and suffered through
```
/** @var ExampleRepository $exampleRepo */
$exampleRepo = $this->em->getRepository(Example::class);

$exampleRepo->myMethod();
```

just to get autocomplete in your IDE?

With this package you can run

```
php public/index.php orm:generate-repository-trait
```

To create a trait that will simplify working with custom repositories.
Just use the trait it creates to get the magic

```
use CustomRepositoryAwareTrait;

public function test()
{
    $result = $this->getExampleRepository()->myMethod();
}
```

## Setup

You can start with

```
composer require curtiskelsey/doctrine-repo-helper
```

then be sure to add

```
'DoctrineRepoHelper'
```

to your Zend application's list of loaded modules.

## Options

Several options are provided to allow control over the trait created and what entities it serves:

```
php public/index.php orm:generate-repository-trait --help

Description:
  Generate a repository helper trait

Usage:
  orm:generate-repository-trait [options]

Options:
      --namespace[=NAMESPACE]  Declares the namespace
  -o, --output[=OUTPUT]        Output path [default: "/var/www"]
  -c, --className[=CLASSNAME]  Classname of the trait [default: "CustomRepositoryAwareTrait"]
      --em-getter[=EM-GETTER]  Property or method name to access the EntityManager [default: "getObjectManager()"]
  -f, --force                  Overwrite existing trait
      --filter[=FILTER]        Filter the list of entities getters are created for
  -h, --help                   Display this help message
  -q, --quiet                  Do not output any message
  -V, --version                Display this application version
      --ansi                   Force ANSI output
      --no-ansi                Disable ANSI output
  -n, --no-interaction         Do not ask any interactive question
  -v|vv|vvv, --verbose         Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
  The generate repository trait command creates a trait that will allow your development
                  environment to autocomplete custom repository methods
```

### Custom output directory
```
php public/index.php orm:generate-repository-trait -o My/Special/Path
```

### Custom trait class name and namespace
```
php public/index.php orm:generate-repository-trait -c SpecialName --namespace=Special\\Namespace
```

### Custom entity manager accessor
```
php public/index.php orm:generate-repository-trait --em-getter=getMyEntityManager()
```

### Overwrite existing trait file
```
php public/index.php orm:generate-repository-trait -f
```

### Filter the entity repositories used within the generated trait
```
php public/index.php orm:generate-repository-trait --filter=Cast
# Only inserts repo getters for entities where "Cast" is found withing the FQCN string
```

## Development

### Quickstart
* `git clone`
* `vagrant up`
* `vagrant ssh`
* `php public/index.php orm:schema-tool:update -f`
* `php public/index.php orm:generate-repository-trait`