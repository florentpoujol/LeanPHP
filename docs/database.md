# Database

LeanPHP does not provide an ORM.

## Migrations

LeanPHP provide a simple migration system to create or modify your table schemas.

### Defining migrations

Migrations are classes that extends `AbstractMigration`, implements the `up()` and `down()` method and use an instance of PDO accessible on the `pdo` property.

Migrations can be defined by default in your project's `database/migrations` folder.  
The name of the files is the name of each migration, and should be prefixed by a number, a timestamp or a date so that they are sortable in the order they should run.

The file must return an instance of the migration object, which can typically be an anonymous class.

Example: 
```php
// in a file named for instance "20241002113100_create_users_table".

return new class extends AbstractMigration
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

Run the `bin/migrations` CLI command, it will run by default with the database connection configured for the current environment.

To run the migration for a specific environment (useful to migrate the tests database), run it with the `--env=test` option (assuming your test env is named `test`).
