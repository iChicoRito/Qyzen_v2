# Task 07: Notification Retention

The `notifications:prune` Artisan command runs every 3 days and deletes only educator notifications that are marked read (`is_read = true`) and whose `created_at` is more than 3 days old. Student and admin notifications are left alone. Unread notifications are retained regardless of age. The command prints the number of deleted rows.

Hostinger must invoke Laravel's scheduler every minute so Laravel can run the every-3-days task:

```cron
* * * * * php /home/<user>/domains/<domain>/artisan schedule:run >> /dev/null 2>&1
```

The command is registered in `routes/console.php` with a `0 0 */3 * *` cron expression and `withoutOverlapping()`. Verify the registration with:

```bash
php artisan schedule:list
```
