<?php

declare(strict_types=1);

namespace Tests\DqlQuerying\Utilities;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Setup;
use EDT\DqlQuerying\Utilities\DeepClassMetadata;
use EDT\DqlQuerying\Utilities\JoinFinder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\data\Model\Book;

class JoinFinderTest extends TestCase
{
    /**
     * @var JoinFinder
     */
    protected $joinFinder;
    /**
     * @var DeepClassMetadata
     */
    private $bookMetadata;

    protected function setUp(): void
    {
        parent::setUp();
        $this->joinFinder = new JoinFinder();
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
        $this->bookMetadata = new DeepClassMetadata(
            $entityManager->getClassMetadata(Book::class),
            $entityManager->getMetadataFactory()
        );
        $bookMetadata = $entityManager->getClassMetadata(Book::class);
        if (!$bookMetadata->hasAssociation('author')) {
            throw new InvalidArgumentException('Doctrine setup seems incorrect');
        }
        if (!$this->bookMetadata->hasAssociation('author')) {
            throw new InvalidArgumentException('Class impl seems incorrect');
        }
    }

    public function testAuthor(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins('', $this->bookMetadata, ['author']);
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_bdafd8c8_Person');
    }

    public function testTitle(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins('', $this->bookMetadata, ['title']);

        self::assertCount(0, $joins);
    }

    public function testName(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins('', $this->bookMetadata, ['author', 'name']);
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_bdafd8c8_Person');
    }

    public function testBirthplace(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins('', $this->bookMetadata, ['author', 'birth']);
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_bdafd8c8_Person');
        $secondJoin = array_shift($joins);
        $this->checkJoin($secondJoin, 't_bdafd8c8_Person.birth', 't_1ad451b9_Birth');
    }

    public function testStreet(): void
    {
        $joins = $this->joinFinder->findNecessaryJoins('', $this->bookMetadata, ['author', 'birth', 'street']);
        $firstJoin = array_shift($joins);
        $this->checkJoin($firstJoin, 'Book.author', 't_bdafd8c8_Person');
        $secondJoin = array_shift($joins);
        $this->checkJoin($secondJoin, 't_bdafd8c8_Person.birth', 't_1ad451b9_Birth');
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
