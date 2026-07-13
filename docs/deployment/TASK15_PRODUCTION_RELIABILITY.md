# Task 15: Production Reliability

## Persistent private storage

Before the next Git deployment, create these directories in Hostinger File Manager outside `public_html` and confirm the site PHP process can write to them:

```text
/home/u560807207/qyzen-backups
/home/u560807207/qyzen-announcement-images
```

Add these values to production `.env`:

```dotenv
DATABASE_BACKUP_ROOT=/home/u560807207/qyzen-backups
ANNOUNCEMENT_IMAGE_ROOT=/home/u560807207/qyzen-announcement-images
```

Move existing `public_html/storage/app/private/backups/*.sql` files into `/home/u560807207/qyzen-backups`. Then clear cached configuration:

```text
/usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan config:clear
```

Copy legacy announcement images with the idempotent migration command:

```text
/usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan announcements:migrate-image-storage
```

If SSH is unavailable, add that command temporarily as an hPanel Custom cron, inspect its output, and delete the cron after it reports the copied/already-present/missing totals. The application keeps a legacy read fallback until all files are copied.

## hPanel cron jobs

Create both as **Custom** jobs scheduled every minute. Enter only the command; do not prepend `* * * * *` or append shell redirection.

```text
/usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan schedule:run
/usr/bin/php /home/u560807207/domains/qyzen.space/public_html/artisan queue:work --stop-when-empty --max-time=55
```

The scheduler runs the daily database backup and daily notification cleanup synchronously. The queue worker is separate and handles queued application jobs; it is not required for scheduled backups.

## Production verification

1. Confirm `php artisan schedule:list` shows daily `backup:database` and `notifications:prune` entries.
2. Run `backup:database`, confirm a new SQL file under `/home/u560807207/qyzen-backups`, and restore a copy into a disposable MySQL database.
3. Upload an announcement image, fetch it as an enrolled student, deploy an update, and fetch it again.
4. Confirm an unrelated student receives `403`, then delete the announcement and confirm the image route returns `404`.
5. Inspect hPanel cron output after both jobs have run.
