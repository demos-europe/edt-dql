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
use \Tests\data\Model\Book;
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
use \Tests\data\Model\Book;
use EDT\DqlQuerying\Utilities\QueryGenerator;
use EDT\DqlQuerying\ConditionFactories\DqlConditionFactory;
$queryGenerator = new QueryGenerator($this->getEntityManager());
$conditionFactory = new DqlConditionFactory();
$condition = $conditionFactory->propertyHasValue('USA', 'authors', 'birth', 'country');
$queryBuilder = $queryGenerator->generateQueryBuilder(Book::class, [$condition]);
```

The main advantage of the second version does not lie in line count or readability but in the removal of the need to manually specify the query in detail.
This allows dynamically receiving queries and directly executing them in the database (provided authorization and validation is checked beforehand).

## Conditions

Beside the `PropertyHasValue` shown above more condition types are supported.
All conditions provided by the library can be found and created using the [`DqlConditionFactory`](../src/DqlQuerying/ConditionFactories/DqlConditionFactory.php) class.

If the provided conditions do not suffice you can [write you own clauses](writing_dql_clauses.md) by implementing [`ClauseInterface`](../src/DqlQuerying/Contracts/ClauseInterface.php).

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
use EDT\DqlQuerying\Utilities\QueryGenerator;
use \Tests\data\Model\Book;
$conditionFactory = new DqlConditionFactory();
$sortingFactory = new SortMethodFactory();
$queryGenerator = new QueryGenerator($this->getEntityManager());
$condition = $conditionFactory->true();
$sortMethods = [
    $sortingFactory->propertyAscending('authors', 'name'),
    $sortingFactory->propertyDescending('authors', 'birthdate'),
];
return $queryGenerator->generateQueryBuilder(Book::class, [$condition], $sortMethods);
```

If the provided sort implementations do not suffice you can [write you own sort implementation](writing_dql_clauses.md#OrderByInterface). 
