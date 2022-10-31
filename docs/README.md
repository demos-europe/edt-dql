# DoctrineQueryGenerator

Helper library for the [Doctrine ORM](https://www.doctrine-project.org/projects/orm.html) to generate
[DQL](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/dql-doctrine-query-language.html)
queries. Currently, the focus lies in the detection of join clauses from a given property path to sort and filter the result set.

A basic understanding of the Doctrine ORM by the user is assumed for this library to be used.

## Example

Suppose we have a `Book`, `Person` and `Address` entity.
 
A `Book` has an `author` property which references `Person` entity.

A `Person` has a `birth` property containing information about the birth location and date.

An `Birth` entity has a `country` property which is a string.

The goal is to get all `Book` entities of which the author was born in the USA.
This could be done by first fetching all entities from the database
and filter the result in PHP. But for performance and memory reasons
it makes sense to execute the filter in the database.

With DQL we could create a query with two joins to get the desired result:

```php
use \Tests\data\DqlModel\Book;
$queryBuilder = $this->getEntityManager()->createQueryBuilder()
    ->select('Book')
    ->from(Book::class, 'Book')
    ->leftJoin('Book.author', 'Person')
    ->leftJoin('Person.birth', 'Birth')
    ->where('Birth.country = :countryName')
    ->setParameter('countryName', 'USA');
```

This library can generate a similar query builder from the following code:

```php
use \Tests\data\DqlModel\Book;
use EDT\DqlQuerying\ConditionFactories\DqlConditionFactory;
use \EDT\DqlQuerying\Utilities\QueryBuilderPreparer;
/** @var \Doctrine\ORM\EntityManager $entityManager */
$entityManager = $this->getEntityManager();
$conditionFactory = new DqlConditionFactory();
$metadataFactory = $entityManager->getMetadataFactory();

$builderPreparer = new QueryBuilderPreparer(Book::class, $metadataFactory, new JoinFinder($metadataFactory));
$builderPreparer->setWhereExpressions([
    $conditionFactory->propertyHasValue('USA', 'authors', 'birth', 'country'),
]);

$builderPreparer->fillQueryBuilder($entityManager->createQueryBuilder());
```

The main advantage of the second version does not lie in line count or readability but in the removal of the need to manually specify the query in detail.
This allows dynamically receiving queries and directly executing them in the database (provided authorization and validation is checked beforehand).

## Conditions

Beside the `PropertyHasValue` shown above more condition types are supported.
All conditions provided by the library can be found and created using the `DqlConditionFactory` class.

If the provided conditions do not suffice you can [write you own clauses](writing_dql_clauses.md) by implementing `ClauseInterface`.

### Condition Nesting

Conditions can be grouped together using `AND` or `OR` conjunctions:

```php
$conditionFactory = new EDT\DqlQuerying\ConditionFactories\DqlConditionFactory();
$andConditions = [ /* ... */];
$orConditions = [ /* ... */];
$orCondition = $conditionFactory->anyConditionApplies(...$orConditions);
$nestedCondition = $conditionFactory->allConditionsApply(...$andConditions);
```

## Sorting

Defining the sorting can be done similarly as defining conditions. In the following example books will be sorted by their authors name as first priority and the authors birthdate as second priority.

```php
use EDT\DqlQuerying\ConditionFactories\DqlConditionFactory;
use EDT\DqlQuerying\SortMethodFactories\SortMethodFactory;
use \Tests\data\DqlModel\Book;
use EDT\DqlQuerying\Utilities\QueryBuilderPreparer;
/** @var \Doctrine\ORM\EntityManager $entityManager */
$entityManager = $this->getEntityManager();
$conditionFactory = new DqlConditionFactory();
$sortingFactory = new SortMethodFactory();
$metadataFactory = $entityManager->getMetadataFactory();

$builderPreparer = new QueryBuilderPreparer(Book::class, $metadataFactory, new JoinFinder($metadataFactory));
$builderPreparer->setSelectExpressions([
    $sortingFactory->propertyAscending('authors', 'name'),
    $sortingFactory->propertyDescending('authors', 'birthdate'),
]);

$builderPreparer->fillQueryBuilder($entityManager->createQueryBuilder());
```

If the provided sort implementations do not suffice you can [write you own sort implementation](writing_dql_clauses.md#OrderByInterface). 

## Credits and acknowledgements

Conception and implementation by Christian Dressler with many thanks to [eFrane](https://github.com/eFrane).
