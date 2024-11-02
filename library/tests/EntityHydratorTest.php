<?php declare(strict_types=1);

namespace Tests\LeanPHP;

use LeanPHP\Container\Container;
use LeanPHP\EntityHydrator\DataToPropertyMap;
use LeanPHP\EntityHydrator\EntityHydrator;
use PHPUnit\Framework\TestCase;
use stdClass;

final class EntityHydratorTest extends TestCase
{
    public function test_map_through_constructor(): void
    {
        // arrange
        $hydrator = new EntityHydrator([
            MyHydratorTestEntity::class => [
                'pk' => 'id',
                'created_at' => 'createdAt',
            ],
        ]);

        $data = [
            'pk' => '1',
            'email' => 'the email',
            'firstName' => null,
            'enum' => 'test',
            'created_at' => '2024-10-05 11:27:00',
            'updated_at' => '2024-10-05 11:27:00',
            'json_data_array' => '[1, 2, 3]',
            'json_data_object' => '{"property":"value"}',
        ];

        Container::get()->alias(\DateTimeInterface::class, \DateTime::class, isSingleton: false);

        // act
        $entity = $hydrator->hydrateOne($data, MyHydratorTestEntity::class);

        // assert
        self::assertSame((int) $data['pk'], $entity->getId());
        self::assertSame($data['email'], $entity->getEmail());
        self::assertSame($data['firstName'], $entity->firstName);
        self::assertSame(MyEnumHydratorTest::TEST, $entity->enum);
        self::assertSame($data['created_at'], $entity->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertInstanceOf(\DateTime::class, $entity->updated_at);
        self::assertSame($data['updated_at'], $entity->updated_at->format('Y-m-d H:i:s'));
        self::assertSame([1, 2, 3], $entity->json_data_array);
        $object = new stdClass();
        $object->property = 'value';
        self::assertEquals($object, $entity->json_data_object);
    }

    public function test_map_through_attribute(): void
    {
        // arrange
        $hydrator = new EntityHydrator();

        $data = [
            'pk' => '1',
            'email' => 'the email',
            'firstName' => null,
            'enum' => 'test',
            'created_at' => '2024-10-05 11:27:00',
            'updated_at' => '2024-10-05 11:27:00',
        ];

        Container::get()->alias(\DateTimeInterface::class, \DateTime::class, isSingleton: false);

        // act
        $entity = $hydrator->hydrateOne($data, MyHydratorTestEntity::class);

        // assert
        self::assertSame((int) $data['pk'], $entity->getId());
        self::assertSame($data['email'], $entity->getEmail());
        self::assertSame($data['firstName'], $entity->firstName);
        self::assertSame(MyEnumHydratorTest::TEST, $entity->enum);
        self::assertSame($data['created_at'], $entity->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertInstanceOf(\DateTime::class, $entity->updated_at);
        self::assertSame($data['updated_at'], $entity->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_map_through_method_call(): void
    {
        // arrange
        $hydrator = new EntityHydrator([
            MyHydratorTestEntity::class => [
                'pk' => 'whatever',
                'created_at' => 'whatever',
            ],
        ]);

        $data = [
            'pk' => '1',
            'email' => 'the email',
            'firstName' => null,
            'enum' => 'test',
            'created_at' => '2024-10-05 11:27:00',
            'updated_at' => '2024-10-05 11:27:00',
        ];

        Container::get()->alias(\DateTimeInterface::class, \DateTime::class, isSingleton: false);

        // act
        $entity = $hydrator->hydrateOne($data, MyHydratorTestEntity::class, [
            'pk' => 'id',
            'created_at' => 'createdAt',
        ]);

        // assert
        self::assertSame((int) $data['pk'], $entity->getId());
        self::assertSame($data['email'], $entity->getEmail());
        self::assertSame($data['firstName'], $entity->firstName);
        self::assertSame(MyEnumHydratorTest::TEST, $entity->enum);
        self::assertSame($data['created_at'], $entity->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertInstanceOf(\DateTime::class, $entity->updated_at);
        self::assertSame($data['updated_at'], $entity->updated_at->format('Y-m-d H:i:s'));
    }
}

enum MyEnumHydratorTest: string
{
    case TEST = 'test';
}

#[DataToPropertyMap([
    'pk' => 'id',
    'created_at' => 'createdAt',
])]
final class MyHydratorTestEntity
{
    private readonly int $id; // @phpstan-ignore-line (Property ... is never written, only read.)
    protected string $email;
    public ?string $firstName;
    public MyEnumHydratorTest $enum;
    private readonly \DateTimeImmutable $createdAt; // @phpstan-ignore-line (Property ... is never written, only read.)
    public \DateTimeInterface $updated_at;
    /**
     * @var array<mixed>
     */
    public array $json_data_array;
    public object $json_data_object;

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
