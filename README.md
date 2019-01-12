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