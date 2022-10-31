# Implementation Details

The following information is not needed by users of the library and instead addresses developers
that intend to adjust or extend the implementation.

## Alias Prefixing

When a DQL query is generated from conditions the entity aliases may be prefixed with
a generated string, e.g. `t_f2a62_Person` instead of just `Person`. The reason why and
how this is done is explained below.

Depending on the use case it may happen that inside a query the same entity property is used for multiple conditions.
This is no problem if the path to that property is the same in both conditions, in which case we could
simply use the unmodified alias. But if the paths differ a conflict occurs without prefixing. 
Take as an example the following query building:

```php
use EDT\DqlQuerying\ConditionFactories\DqlConditionFactory;
use \Tests\data\DqlModel\Book;
use EDT\DqlQuerying\Utilities\QueryBuilderPreparer;
/** @var \Doctrine\ORM\EntityManager $entityManager */
$entityManager = $this->getEntityManager();
$authorName = 'Charles Dickens';
$conditionFactory = new DqlConditionFactory();
$metadataFactory = $entityManager->getMetadataFactory();

$builderPreparer = new QueryBuilderPreparer(Book::class, $metadataFactory, new JoinFinder($metadataFactory));
$builderPreparer->setWhereExpressions([
    $conditionFactory->propertyHasValue($authorName, 'authors', 'name'),
    $conditionFactory->propertyHasValue($authorName, 'editors', 'name')
]);

$builderPreparer->fillQueryBuilder($entityManager->createQueryBuilder());
```

In this query we look up books that were authored and edited by the same person (or two people with the same name).
The target of both equality conditions is the `name` property in the `Person` entity.
**Without** prefixing the resulting query is similar to the DQL resulting from the following, manually created query:

```php
use \Tests\data\DqlModel\Book;
$queryBuilder = $this->getEntityManager()->createQueryBuilder()
    ->select('Book')
    ->from(Book::class, 'Book')
    ->leftJoin('Book.authors', 'Person')
    ->leftJoin('Book.editors', 'Person')
    ->where('Person.name = :authorName')
    ->where('Person.name = :authorName')
    ->setParameter('authorName', 'Charles Dickens');
```

However, this query is erroneous because the same alias for two different
joins (both leading to the `Person` entity) is used.
Once in the join to `Book.author` and once for the join to `Book.editor`.
To differentiate the paths we generate prefixes for the entity aliases.
The actually generated DQL is thus similar to the following:

```php
use \Tests\data\DqlModel\Book;
$queryBuilder = $this->getEntityManager()->createQueryBuilder()
    ->select('Book')
    ->from(Book::class, 'Book')
    ->leftJoin('Book.authors', 't_f2a62_Person')
    ->leftJoin('Book.editors', 't_da01f_Person')
    ->where('t_f2a62_Person.name = :authorName')
    ->where('t_da01f_Person.name = :authorName')
    ->setParameter('authorName', 'Charles Dickens');
```

The generated prefix is based on the property path leading to the target property.
It is **not** generated randomly but by using a hash function. Due to that for the same path the
same prefix will be generated even if the path is used in different conditions, which will avoid
unnecessary join clauses in cases where the join is actually the same.


