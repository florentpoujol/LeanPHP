# Database

LeanPHP does not provide an ORM.

## Migrations

LeanPHP provide a simple migration system to create or modify your table schemas.

### Defining migrations

Migrations are classes with `up()` and `down()` method and an instance of PDO accessible on the `pdo` property.

```php
final class CreateUserTable extends AbstractMigration
{
    public function up(): void
    {
        $this->pdo->exec(<<<SQLite
        create table users
        (
            id integer constraint users_pk primary key autoincrement,
            email TEXT not null constraint users_email_unique unique
        );
        SQLite);    
    }
    
    public function down(): void
    {
        $this->pdo->exec(<<<SQLite
        drop table users;
        SQLite);    
    }
}
```

### Running migrations

```
migration fresh
migration up
migration down
```
