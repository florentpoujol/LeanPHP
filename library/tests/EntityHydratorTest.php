<?php declare(strict_types=1);

namespace Tests\LeanPHP;

use LeanPHP\Container;
use LeanPHP\EntityHydrator\DataToPropertyMap;
use LeanPHP\EntityHydrator\EntityHydrator;
use MainTest;
use PHPUnit\Framework\TestCase;

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
            'created_at' => '2024-10-05 11:27:00',
            'updated_at' => '2024-10-05 11:27:00',
        ];

        Container::getInstance()->bind(\DateTimeInterface::class, \DateTime::class, isSingleton: false);

        // act
        $entity = $hydrator->hydrateOne($data, MyHydratorTestEntity::class);

        // assert
        self::assertSame((int) $data['pk'], $entity->getId());
        self::assertSame($data['email'], $entity->getEmail());
        self::assertSame($data['firstName'], $entity->firstName);
        self::assertSame($data['created_at'], $entity->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertInstanceOf(\DateTime::class, $entity->updated_at);
        self::assertSame($data['updated_at'], $entity->updated_at->format('Y-m-d H:i:s'));
    }

    public function test_map_through_attribute(): void
    {
        // arrange
        $hydrator = new EntityHydrator();

        $data = [
            'pk' => '1',
            'email' => 'the email',
            'firstName' => null,
            'created_at' => '2024-10-05 11:27:00',
            'updated_at' => '2024-10-05 11:27:00',
        ];

        Container::getInstance()->bind(\DateTimeInterface::class, \DateTime::class, isSingleton: false);

        // act
        $entity = $hydrator->hydrateOne($data, MyHydratorTestEntity::class);

        // assert
        self::assertSame((int) $data['pk'], $entity->getId());
        self::assertSame($data['email'], $entity->getEmail());
        self::assertSame($data['firstName'], $entity->firstName);
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
            'created_at' => '2024-10-05 11:27:00',
            'updated_at' => '2024-10-05 11:27:00',
        ];

        Container::getInstance()->bind(\DateTimeInterface::class, \DateTime::class, isSingleton: false);

        // act
        $entity = $hydrator->hydrateOne($data, MyHydratorTestEntity::class, [
            'pk' => 'id',
            'created_at' => 'createdAt',
        ]);

        // assert
        self::assertSame((int) $data['pk'], $entity->getId());
        self::assertSame($data['email'], $entity->getEmail());
        self::assertSame($data['firstName'], $entity->firstName);
        self::assertSame($data['created_at'], $entity->getCreatedAt()->format('Y-m-d H:i:s'));
        self::assertInstanceOf(\DateTime::class, $entity->updated_at);
        self::assertSame($data['updated_at'], $entity->updated_at->format('Y-m-d H:i:s'));
    }
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
    private readonly \DateTimeImmutable $createdAt; // @phpstan-ignore-line (Property ... is never written, only read.)
    public \DateTimeInterface $updated_at;

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
