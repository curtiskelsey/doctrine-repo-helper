# Doctrine Repo Helper

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