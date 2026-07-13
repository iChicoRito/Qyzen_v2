# Task 07: Notification Retention

The `notifications:prune` Artisan command runs daily and deletes read or unread educator and student notifications whose `created_at` is at least 3 days old. Admin notifications are retained. The command prints the number of deleted rows.

Hostinger must invoke Laravel's scheduler every minute so Laravel can run the every-3-days task:

```text
/usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan schedule:run
```

In hPanel, select a **Custom** cron and schedule it every minute. Do not add a cron prefix or shell redirection to the command field. Laravel registers notification pruning as daily with `withoutOverlapping()`. Verify the registration with:

```bash
php artisan schedule:list
```
