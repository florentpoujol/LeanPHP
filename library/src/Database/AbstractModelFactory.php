<?php declare(strict_types=1);

namespace LeanPHP\Database;

/**
 * @template Model of AbstractModel
 */
abstract class AbstractModelFactory
{
    public function __construct(
        protected readonly \PDO $pdo,
        /**
         * @var QueryBuilder<Model>
         */
        protected readonly QueryBuilder $queryBuilder,
    ) {
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function getModelFqcn(): string;

    /**
     * @return array<string, null|scalar>
     */
    abstract protected function getDatabaseRowData(): array;

    /**
     * @param array<string, null|scalar> $row
     *
     * @return Model
     */
    public function makeOne(array $row = []): object
    {
        $row = array_merge($this->getDatabaseRowData(), $row);

        return $this->getModelFqcn()::fromDatabaseRow($row);
    }

    /**
     * @param array<array<string, null|scalar>> $rows
     *
     * @return array<Model>
     */
    public function makeMany(int $count, array $rows = []): array
    {
        $modelFqcn = $this->getModelFqcn();
        $models = [];

        for ($i = 0; $i < $count; $i++) {
            if (isset($rows[$i])) {
                $row = array_merge($this->getDatabaseRowData(), $rows[$i]);
            } else {
                $row = $this->getDatabaseRowData();
            }

            $models[] = $modelFqcn::fromDatabaseRow($row);
        }

        return $models;
    }

    /**
     * @param array<string, null|scalar> $row
     */
    public function saveOne(array $row = []): void
    {
        $this->saveMany(1, [$row]);
    }

    /**
     * @param array<array<string, null|scalar>> $rows
     */
    public function saveMany(int $count, array $rows = []): void
    {
        $entities = $this->makeMany($count, $rows);

        $rows = [];
        foreach ($entities as $entity) {
            $rows[] = $entity->toDatabaseRow();
        }

        $this->queryBuilder
            ->inTable($this->getModelFqcn()::getDatabaseTable())
            ->insertMany($rows);
    }
}