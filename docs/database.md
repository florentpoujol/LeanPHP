# Database

LeanPHP does not provide an ORM.

## Migrations

LeanPHP provide a simple migration system to create or modify your table schemas.

### Defining migrations

Migrations can be defined by default in your project's `database/migrations` folder.  
The name of the files is the name of each migration, and should be prefixed by a number, a timestamp or a date so that they are sortable in the order they should run.

Migration files can just be `.sql` files.

Or migrations can be `.php` files with classes that extends `AbstractMigration`, implements the `up()` and `down()` method and use an instance of PDO accessible on the `pdo` property.  
The file must return an instance of the migration object, which can typically be an anonymous class.

Example: 
```php
// in a file named for instance "20241002113100_create_users_table.php".

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

The migrations that have already run are saved in a table named by default `leanphp_migrations` so that running the command only run the migration that haven't been applied already.


## Seeding data

## Writing seeders

Similar than migrations, your project's `database/seeders` folder can contain files which aim to fill the database instead of changing its schema.

The name of the files should be prefixed by a number, a timestamp or a date so that they are sortable in the order they should run.

The files can be regular `.sql` files, or `.php` files that return an instance of a class that extends `AbstractSeeder`.

## Running seeders

Run the `bin/seeders` CLI command, it will run by default with the database connection configured for the current environment.

To run the seeders for a specific environment (useful to target the tests database), run it with the `--env=test` option (assuming your test env is named `test`).

Unlike migrations, we do not keep track of which seeders have run. 
Typically, this is fine since the main usage of seeder is to fill a database just after it has been freshly migrated.

You can also run a single seeder file by putting its name as argument : 
`bin/seeders 20241002155300_my_seeder`.