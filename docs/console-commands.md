# Console commands

- we want a single actual command that is an entry point
- we want this command to find user and framework-defined commands and display a liste of them
- we want for all the defined commands, to automatically have a 'help' command that display all defined arguments and options + description for the command and arguments and options

commands are like controllers: arguments are route parameters, options are query string
```php

final readonly class Command
{
    #[RunAsCliCommand(name: 'migrate')] // ie: bin/console migrate up --dry-run
    public function command(
        #[CliArgument] MigrateDirection $direction,
        #[CliOption()] bool $dryRun = false,
        #[CliOption(aliases: ['v'])] bool $verbose = false,
        ArgvInput $input, 
        EchoOutput $output, 
    ): int {
        //    
    }
    
    #[RunAsCommand(name: 'migrate')] // ie: bin/console migrate up --dry-run
    public function command(
        #[CliArgument] MigrateDirection $direction,
        #[CliOption()] bool $dryRun = false,
        #[CliOption(aliases: ['v'])] bool $verbose = false,
        ArgvInput $input, 
        EchoOutput $output, 
    ): int {
        //    
    }
}
```


```php
#[CliOption(name: 'dry-run', type: boolean, defaultValue: false)]
#[CliOption]
final readonly class Command
{
    
    #[RunAsCommand(name: 'migrate:up')] // ie: bin/console migrate:up --dry-run
    public function command(
        #[CliArgument] MigrateDirection $direction,
        #[CliOption()] bool $dryRun = false,
        #[CliOption(aliases: ['v'])] bool $verbose = false,
        ArgvInput $input,
        EchoOutput $output,
    ): int {
        //    
    }
    
    #[RunAsCommand(name: 'migrate')] // ie: bin/console migrate up --dry-run
    public function command(
        #[CliArgument] MigrateDirection $direction,
        #[CliArgument]
        #[Validate([Rule::pattern => '/[a-z]{5}/'])] 
        string $stuff,
        #[CliOption] bool $dryRun = false,
        #[CliOption(aliases: ['v'])] bool $verbose = false,
        ArgvInput $input, 
        EchoOutput $output, 
    ): int {
        //    
    }
}
```


```php
final class MyCommandInput
{
    public function __construct(
        #[CliArgument]
        public readonly string $arg1,
        #[CliOption]
        public bool $dryRun = false,
    ) {
    }
}

final readonly class Command
{
    
    #[RunAsCommand(name: 'migrate:up')] // ie: bin/console migrate:up --dry-run
    public function command(
        #[MapCliInput] MyCommandInput $input,
    ): int {
            
    }
}

[
    'migrate:up' => [
        'arguments' => [
            'arg1' => [
                'description' => '...',
                'pattern' => '...',
                'mandatory' => true,
                'default' => '...',            
            ]                   
        ],   
        'options' => [
            'dry-run' => [
                'description' => '...',
                'pattern' => '...',
                'default' => '...',
                
                'aliases' => '...',
                'boolean' => true,             
            ]           
        ],
    ]
]



```
