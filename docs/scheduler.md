# Scheduling tasks

To run a piece of code periodically you can define a CLI command for it and then call it via your system's cron.

If you have many such scheduled task, or in many cloud environments, this solution is not practical.

Instead, you can run the sole `RunScheduledTask` CLI command via cron or any other way (a bash infinite loop also works) for instance every minute.
This command will run Lean's scheduler system and run any scheduled tasks that you have defined in your code if any is up to run.

## Keeping track of last and next run times

To run efficiently and preventing running tasks more than they need and debugging, the scheduler keeps track of every defined tasks as well as their next and last run times.

Ideally this information should be kept in a central storage, by default it is kept in the cache, but it can also in the database, which makes it easy to observe all the tasks and their information.

The command has several sub command
- sync scheduled task : find the tasks in the app and revamp config (with dry run)
- run scheduled command
- run command (hash)

## Defining scheduled tasks

You can add the `AsScheduledTask` attribute to any class.

```php
#[AsScheduledTask(cron: '15 0 * * 1-5', description: '')]
#[AsScheduledTask(dayOfWeek: 'MON-SAT', hour: '0', minute: '15', method: 'handle')]
final readonly class MyService
{

}
```

The service will be run synchronously, a method `runAsScheduled()` will be called. Its return value will be discarded.

If you want another method to be called, pass its name to the attribute's `method` argument.

If the class also has the `#[AsAsynJob]` attribute, the job will be automatically queued instead of be run synchronously.

 