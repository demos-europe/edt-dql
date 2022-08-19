<?php

declare(strict_types=1);

namespace Tests\DqlQuerying\Utilities;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Setup;
use EDT\DqlQuerying\Contracts\MappingException;
use EDT\DqlQuerying\Utilities\JoinFinder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\data\DqlModel\Book;
use Tests\data\DqlModel\Person;

class JoinFinderTest extends TestCase
{
    /**
     * @var JoinFinder
     */
    protected $joinFinder;
    /**
     * @var ClassMetadata
     */
    private $bookMetadata;

    /**
     * @var ClassMetadata
     */
    private $personMetadata;

    protected function setUp(): void
    {
        parent::setUp();
        $config = Setup::createAnnotationMetadataConfiguration(
            [__DIR__.'/tests/Model'],
            true,
            null,
            null,
            false
        );
        $paths = [__DIR__.'/tests/Model'];
        $driver = new AnnotationDriver(new AnnotationReader(), $paths);
        // registering noop annotation autoloader - allow all annotations by default
        AnnotationRegistry::registerLoader('class_exists');
        $config->setMetadataDriverImpl($driver);
        $conn = [
            'driver' => 'pdo_sqlite',
            'path' => __DIR__ . '/db.sqlite',
        ];
        $entityManager = EntityManager::create($conn, $config);
        $this->bookMetadata = $entityManager->getClassMetadata(Book::class);
        $this->personMetadata = $entityManager->getClassMetadata(Person::class);
        $this->joinFinder = new JoinFinder($entityManager->getMetadataFactory());
        if (!$this->bookMetadata->hasAssociation('author')) {
            throw new InvalidArgumentException('Class impl or Doctrine setup seems incorrect');
        }
    }

    public function testAuthor(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins(true, '', $this->bookMetadata, ['author'], $this->bookMetadata->getTableName());
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_58fb870d_Person');
    }

    public function testToManyDisallowd(): void
    {
        $this->expectException(MappingException::class);
        $this->joinFinder->findNecessaryJoins(false, '', $this->personMetadata, ['books'], $this->personMetadata->getTableName());
    }

    public function testToManyAllowed(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins(true, '', $this->personMetadata, ['books'], $this->personMetadata->getTableName());
        /** @var Join $join */
        $join = array_shift($joins);
        if (null === $join) {
            self::fail();
        }
        self::assertNotNull($join);
        self::assertSame('Person.books', $join->getJoin());
        self::assertSame('t_3e6230ca_Book', $join->getAlias());
        self::assertSame(Join::LEFT_JOIN, $join->getJoinType());
        self::assertNull($join->getCondition());
        self::assertNull($join->getIndexBy());
    }

    public function testTitle(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins(true, '', $this->bookMetadata, ['title'], $this->bookMetadata->getTableName());

        self::assertCount(0, $joins);
    }

    public function testName(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins(true, '', $this->bookMetadata, ['author', 'name'], $this->bookMetadata->getTableName());
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_58fb870d_Person');
    }

    public function testBirthplace(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins(true, '', $this->bookMetadata, ['author', 'birth'], $this->bookMetadata->getTableName());
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_58fb870d_Person');
        $secondJoin = array_shift($joins);
        $this->checkJoin($secondJoin, 't_58fb870d_Person.birth', 't_7e118c84_Birth');
    }

    public function testStreet(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins(true, '', $this->bookMetadata, ['author', 'birth', 'street'], $this->bookMetadata->getTableName());
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_58fb870d_Person');
        $secondJoin = array_shift($joins);
        $this->checkJoin($secondJoin, 't_58fb870d_Person.birth', 't_7e118c84_Birth');
    }

    private function checkJoin(?Join $join, string $left, string $right): void
    {
        if (null === $join) {
            self::fail();
        }
        self::assertNotNull($join);
        self::assertSame($left, $join->getJoin());
        self::assertSame($right, $join->getAlias());
        self::assertSame(Join::LEFT_JOIN, $join->getJoinType());
        self::assertNull($join->getCondition());
        self::assertNull($join->getIndexBy());
    }
}
