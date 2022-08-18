<?php

declare(strict_types=1);

namespace Tests\DqlQuerying\Utilities;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Tools\Setup;
use EDT\DqlQuerying\ConditionFactories\DqlConditionFactory;
use EDT\DqlQuerying\Functions\AllEqual;
use EDT\DqlQuerying\Functions\AllTrue;
use EDT\DqlQuerying\Functions\Product;
use EDT\DqlQuerying\Functions\Property;
use EDT\DqlQuerying\Functions\Size;
use EDT\DqlQuerying\Functions\Sum;
use EDT\DqlQuerying\Functions\UpperCase;
use EDT\DqlQuerying\Functions\Value;
use EDT\DqlQuerying\Utilities\QueryGenerator;
use EDT\DqlQuerying\SortMethodFactories\SortMethodFactory;
use EDT\Querying\Contracts\PropertyPathAccessInterface;
use EDT\Querying\PropertyPaths\PropertyPath;
use PHPUnit\Framework\TestCase;
use Tests\data\DqlModel\Book;
use Tests\data\DqlModel\Person;

class QueryGeneratorTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var DqlConditionFactory
     */
    protected $conditionFactory;

    /**
     * @var SortMethodFactory
     */
    protected $sortingFactory;
    /**
     * @var QueryGenerator
     */
    private $queryGenerator;

    protected function setUp(): void
    {
        parent::setUp();
        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__.'/tests/data/Model'],
            true,
            null,
            null,
            false
        );
        $paths = [__DIR__.'/tests/data/Model'];
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);
        // registering noop annotation autoloader - allow all annotations by default
        AnnotationRegistry::registerLoader('class_exists');
        $config->setMetadataDriverImpl($driver);
        $conn = [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        ];
        $this->entityManager = EntityManager::create($conn, $config);
        $this->queryGenerator = new QueryGenerator($this->entityManager);
        $this->conditionFactory = new DqlConditionFactory();
        $this->sortingFactory = new SortMethodFactory();
    }

    public function testTestsetup(): void
    {
        $metadata = $this->entityManager->getClassMetadata(Book::class);
        self::assertSame(Book::class, $metadata->name);
    }

    public function testAlwaysTrue(): void
    {
        $trueCondition = $this->conditionFactory->true();
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$trueCondition]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE 1 = 1',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testAlwaysFalse(): void
    {
        $trueCondition = $this->conditionFactory->false();
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$trueCondition]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE 1 = 2',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testAnyConditionApplies(): void
    {
        $emptyTitleCondition = $this->conditionFactory->propertyHasValue('', 'title');
        $nullTitleCondition = $this->conditionFactory->propertyIsNull('title');
        $allConditionsApply = $this->conditionFactory->anyConditionApplies($emptyTitleCondition, $nullTitleCondition);
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$allConditionsApply]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE Book.title = ?0 OR Book.title IS NULL',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('', $parameters->first()->getValue());
    }

    public function testAllConditionsApply(): void
    {
        $bookA = $this->conditionFactory->propertyHasValue('Harry Potter and the Philosopher\'s Stone', 'books', 'title');
        $bookB = $this->conditionFactory->propertyHasValue('Harry Potter and the Deathly Hallows', 'books', 'title');
        $allConditionsApply = $this->conditionFactory->allConditionsApply($bookA, $bookB);
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$allConditionsApply]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person LEFT JOIN Person.books t_3e6230ca_Book WHERE t_3e6230ca_Book.title = ?0 AND t_3e6230ca_Book.title = ?1',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(2, $parameters);
        self::assertSame('Harry Potter and the Philosopher\'s Stone', $parameters->first()->getValue());
        self::assertSame('Harry Potter and the Deathly Hallows', $parameters->last()->getValue());
    }

    public function testEqualsWithoutSorting(): void
    {
        $propertyHasValue = $this->conditionFactory->propertyHasValue('Example Street', 'author', 'birth', 'street');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$propertyHasValue]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.street = ?0',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('Example Street', $parameters->first()->getValue());
    }

    public function testEqualsWithAscendingFirstSorting(): void
    {
        $propertyHasValue = $this->conditionFactory->propertyHasValue('Example Street', 'author', 'birth', 'street');
        $ascending = $this->sortingFactory->propertyAscending('author', 'birth', 'street');
        $descending = $this->sortingFactory->propertyDescending('title');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(
            Book::class,
            [$propertyHasValue],
            [$ascending, $descending]
        );
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.street = ?0 ORDER BY t_7e118c84_Birth.street ASC, Book.title DESC',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('Example Street', $parameters->first()->getValue());
    }

    public function testEqualsWithDescendingFirstSorting(): void
    {
        $propertyHasValue = $this->conditionFactory->propertyHasValue('Example Street', 'author', 'birth', 'street');
        $descending = $this->sortingFactory->propertyDescending('author', 'birth', 'street');
        $ascending = $this->sortingFactory->propertyAscending('title');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(
            Book::class,
            [$propertyHasValue],
            [$descending, $ascending]
        );
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.street = ?0 ORDER BY t_7e118c84_Birth.street DESC, Book.title ASC',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('Example Street', $parameters->first()->getValue());
    }

    public function testEqualsWithAscendingFirstSortingAndPagination(): void
    {
        $propertyHasValue = $this->conditionFactory->propertyHasValue('Example Street', 'author', 'birth', 'street');
        $ascending = $this->sortingFactory->propertyAscending('author', 'birth', 'street');
        $descending = $this->sortingFactory->propertyDescending('title');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(
            Book::class,
            [$propertyHasValue],
            [$ascending, $descending],
            1, 3
        );
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.street = ?0 ORDER BY t_7e118c84_Birth.street ASC, Book.title DESC',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('Example Street', $parameters->first()->getValue());
        self::assertSame(1, $queryBuilder->getFirstResult());
        self::assertSame(3, $queryBuilder->getMaxResults());
    }

    public function testNullRelationship(): void
    {
        $propertyIsNull = $this->conditionFactory->propertyIsNull('author', 'birth');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$propertyIsNull]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth IS NULL',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testNullNonRelationship(): void
    {
        $propertyIsNull = $this->conditionFactory->propertyIsNull('author', 'birth', 'street');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$propertyIsNull]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.street IS NULL',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testEmptyRelationship(): void
    {
        $propertyHasSize = $this->conditionFactory->propertyHasSize(0, 'author');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$propertyHasSize]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE SIZE(Book.author) = ?0',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame(0, $parameters->first()->getValue());
    }

    public function testNonEmptyRelationship(): void
    {
        $propertyHasNotSize = $this->conditionFactory->propertyHasNotSize(0, 'books');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$propertyHasNotSize]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person WHERE NOT(SIZE(Person.books) = ?0)',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame(0, $parameters->first()->getValue());
    }

    public function testNonEmptyRelationshipNested(): void
    {
        $propertyHasNotSize = $this->conditionFactory->propertyHasNotSize(0, 'author', 'books');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$propertyHasNotSize]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person WHERE NOT(SIZE(t_58fb870d_Person.books) = ?0)',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame(0, $parameters->first()->getValue());
    }

    public function testBetweenValues(): void
    {
        $propertyBetween = $this->conditionFactory->propertyBetweenValuesInclusive(-1, 5, 'author', 'birth', 'streetNumber');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$propertyBetween]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.streetNumber BETWEEN ?0 AND ?1',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(2, $parameters);
        self::assertSame(-1, $parameters->first()->getValue());
        self::assertSame(5, $parameters->last()->getValue());
    }

    public function testContainsValueCaseInsensitive(): void
    {
        $containsValue = $this->conditionFactory->propertyHasStringContainingCaseInsensitiveValue('Ave', 'author', 'birth', 'street');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$containsValue]);
        self::assertSame(
            "SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE LOWER(t_7e118c84_Birth.street) LIKE CONCAT('%', LOWER(?0), '%')",
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $queryBuilder->getParameters());
        self::assertSame('Ave', $parameters->first()->getValue());
    }

    public function testOneOfValues(): void
    {
        $containsValue = $this->conditionFactory->propertyHasAnyOfValues([1, 2, 3], 'author', 'birth', 'streetNumber');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$containsValue]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.streetNumber IN(?0)',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame([1, 2, 3], $parameters->first()->getValue());
    }

    public function testOneOfValuesWithEmptyArray(): void
    {
        $containsValue = $this->conditionFactory->propertyHasAnyOfValues([], 'author', 'birth', 'streetNumber');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$containsValue]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE 1 = 2',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testNotOneOfValuesWithEmptyArray(): void
    {
        $containsValue = $this->conditionFactory->propertyHasNotAnyOfValues([], 'author', 'birth', 'streetNumber');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$containsValue]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE NOT(1 = 2)',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testPropertyHasStringAsMember(): void
    {
        $novelBook = $this->conditionFactory->propertyHasStringAsMember('Novel', 'tags');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$novelBook]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE ?0 MEMBER OF Book.tags',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('Novel', $parameters->first()->getValue());
    }

    public function testPropertyHasNotStringAsMember(): void
    {
        $noNovelBook = $this->conditionFactory->propertyHasNotStringAsMember('Novel', 'tags');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$noNovelBook]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE NOT(?0 MEMBER OF Book.tags)',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('Novel', $parameters->first()->getValue());
    }

    public function testPropertiesEqual(): void
    {
        $birthDateCondition = $this->conditionFactory->propertiesEqual(['author', 'birth', 'month'], ['author', 'birth', 'day']);
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$birthDateCondition]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth WHERE t_7e118c84_Birth.month = t_7e118c84_Birth.day',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testPropertiesEqualWithForeignEntityClass(): void
    {
        $birthDateCondition = $this->conditionFactory->propertiesEqual(
            ['author', 'birth', 'month'],
            ['author', 'birth', 'day'],
            Book::class
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$birthDateCondition]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book LEFT JOIN Book.author t_58fb870d_Person LEFT JOIN t_58fb870d_Person.birth t_7e118c84_Birth, Tests\data\DqlModel\Book t__Book LEFT JOIN t__Book.author t_71115441_Person LEFT JOIN t_71115441_Person.birth t_1a171a0d_Birth WHERE t_7e118c84_Birth.month = t_1a171a0d_Birth.day',
            $queryBuilder->getDQL()
        );
        self::assertCount(0, $queryBuilder->getParameters());
    }

    public function testUpperCase(): void
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, 'title');
        $sameUpperCase = new AllEqual(
            new UpperCase(new Property($propertyPath)),
            new Value('FOO'),
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Book::class, [$sameUpperCase]);
        self::assertSame(
            'SELECT Book FROM Tests\data\DqlModel\Book Book WHERE UPPER(Book.title) = ?0',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame('FOO', $parameters->first()->getValue());
    }

    public function testSum(): void
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, 'name');
        $size = new Size(new Property($propertyPathInstance));
        $sum = new AllEqual(
            new Sum($size, $size),
            new Value(4)
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$sum]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person WHERE SIZE(Person.name) + SIZE(Person.name) = ?0',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame(4, $parameters->first()->getValue());
    }

    public function testSumAdditionalAddends(): void
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, 'name');
        $size = new Size(new Property($propertyPathInstance));
        $sum = new AllEqual(
            new Sum($size, $size, $size, $size),
            new Value(8)
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$sum]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person WHERE ((SIZE(Person.name) + SIZE(Person.name)) + SIZE(Person.name)) + SIZE(Person.name) = ?0',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(1, $parameters);
        self::assertSame(8, $parameters->first()->getValue());
    }

    public function testSumPowMixed(): void
    {
        $propertyPathInstance = new PropertyPath(null, '', PropertyPathAccessInterface::UNPACK, 'name');
        $size = new Size(new Property($propertyPathInstance));
        $sum = new AllEqual(
            new Product(
                new Product(new Sum($size, $size), new Value(2)),
                new Sum($size, $size)
            ),
            new Sum(
                new Value(8),
                new Product(new Sum($size, $size), new Value(2)),
                new Value(8)
            )
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$sum]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person WHERE ((SIZE(Person.name) + SIZE(Person.name)) * ?0) * (SIZE(Person.name) + SIZE(Person.name)) = (?1 + ((SIZE(Person.name) + SIZE(Person.name)) * ?2)) + ?3',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(4, $queryBuilder->getParameters());
        self::assertSame(2, $parameters->first()->getValue());
        self::assertSame(8, $parameters->next()->getValue());
        self::assertSame(2, $parameters->next()->getValue());
        self::assertSame(8, $parameters->next()->getValue());
    }

    public function testCustomMemberCondition(): void
    {
        $propertyPath = new PropertyPath(null, '', PropertyPathAccessInterface::DIRECT, 'books', 'title');
        $condition = new AllTrue(
            new AllEqual(
                new Value('Harry Potter and the Philosopher\'s Stone'),
                new Property($propertyPath)
            ),
            new AllEqual(
                new Value('Harry Potter and the Deathly Hallows'),
                new Property($propertyPath),
            )
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$condition]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person LEFT JOIN Person.books t_3e6230ca_Book WHERE ?0 = t_3e6230ca_Book.title AND ?1 = t_3e6230ca_Book.title',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(2, $queryBuilder->getParameters());
        self::assertSame("Harry Potter and the Philosopher's Stone", $parameters->first()->getValue());
        self::assertSame('Harry Potter and the Deathly Hallows', $parameters->next()->getValue());
    }

    public function testCustomMemberConditionWithSalt(): void
    {
        $propertyPathA = new PropertyPath(null, 'a', PropertyPathAccessInterface::DIRECT, 'books', 'title');
        $propertyPathB = new PropertyPath(null, 'b', PropertyPathAccessInterface::DIRECT, 'books', 'title');
        $condition = new AllTrue(
            new AllEqual(
                new Property($propertyPathA),
                new Value('Harry Potter and the Philosopher\'s Stone')
            ),
            new AllEqual(
                new Property($propertyPathB),
                new Value('Harry Potter and the Deathly Hallows')
            )
        );
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$condition]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person LEFT JOIN Person.books t_99a6b3fc_Book LEFT JOIN Person.books t_246cdf32_Book WHERE t_99a6b3fc_Book.title = ?0 AND t_246cdf32_Book.title = ?1',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(2, $parameters);
        self::assertSame("Harry Potter and the Philosopher's Stone", $parameters->first()->getValue());
        self::assertSame('Harry Potter and the Deathly Hallows', $parameters->next()->getValue());
    }

    public function testAllValuesPresentInMemberListProperties(): void
    {
        $condition = $this->conditionFactory->allValuesPresentInMemberListProperties([
            'Harry Potter and the Philosopher\'s Stone',
            'Harry Potter and the Deathly Hallows'
        ], 'books', 'title');
        $queryBuilder = $this->queryGenerator->generateQueryBuilder(Person::class, [$condition]);
        self::assertSame(
            'SELECT Person FROM Tests\data\DqlModel\Person Person LEFT JOIN Person.books t_4dba5d08_Book LEFT JOIN Person.books t_902c848d_Book WHERE t_4dba5d08_Book.title = ?0 AND t_902c848d_Book.title = ?1',
            $queryBuilder->getDQL()
        );

        /** @var Collection<int, Parameter> $parameters */
        $parameters = $queryBuilder->getParameters();
        self::assertCount(2, $parameters);
        self::assertSame("Harry Potter and the Philosopher's Stone", $parameters->first()->getValue());
        self::assertSame('Harry Potter and the Deathly Hallows', $parameters->next()->getValue());
    }
}
