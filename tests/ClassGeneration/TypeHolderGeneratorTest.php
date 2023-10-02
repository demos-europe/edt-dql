<?php

declare(strict_types=1);

namespace Tests\ClassGeneration;

use EDT\DqlQuerying\ClassGeneration\TypeHolderGenerator;
use EDT\Parsing\Utilities\ClassOrInterfaceType;
use EDT\Parsing\Utilities\NonClassOrInterfaceType;
use PHPUnit\Framework\TestCase;
use Tests\data\ApiTypes\AuthorType;
use Tests\data\ApiTypes\BookType;

class TypeHolderGeneratorTest extends TestCase
{
    private const RESULT = '<?php

declare(strict_types=1);

namespace Bar;

use Tests\data\ApiTypes\AuthorType;
use Tests\data\ApiTypes\BookType;

/**
 * WARNING: THIS CLASS IS AUTOGENERATED.
 * MANUAL CHANGES WILL BE LOST ON RE-GENERATION.
 */
class Foo
{
	/** @var AuthorType */
	protected AuthorType $authorType;

	/** @var BookType<bool,\'foobar\'> */
	protected BookType $bookType;


	/**
	 * @param AuthorType
	 * @param BookType<bool,\'foobar\'>
	 */
	public function __construct(AuthorType $authorType, BookType $bookType)
	{
		$this->authorType = authorType;
		$this->bookType = bookType;
	}


	/**
	 * @return AuthorType
	 */
	public function getAuthorType(): AuthorType
	{
		return $this->authorType;
	}


	/**
	 * @return BookType<bool,\'foobar\'>
	 */
	public function getBookType(): BookType
	{
		return $this->bookType;
	}
}
';
    public function testgenerateTypeHolder(): void
    {
        $generator = new TypeHolderGenerator();
        $phpFile = $generator->generateTypeHolder([
            ClassOrInterfaceType::fromFqcn(AuthorType::class),
            ClassOrInterfaceType::fromFqcn(
                BookType::class,
                [
                    NonClassOrInterfaceType::fromRawString('bool'),
                    NonClassOrInterfaceType::fromRawString("'foobar'")
                ]
            ),
        ], 'Foo', 'Bar');

        self::assertSame(self::RESULT, (string)$phpFile);
    }
}
