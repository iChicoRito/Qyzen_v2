# Task 07: Profile media persistence

Profile pictures and cover photos are stored on the `profile_media` local disk.
The disk root must be outside the repository so deploys and Git cleanup cannot
remove uploaded media.

Set these values in the server's `.env` (do not commit that file):

```dotenv
PROFILE_MEDIA_ROOT=/var/lib/qyzen/profile-media
PROFILE_MEDIA_URL=https://example.com/profile-media
```

Create the directory, grant the web/PHP user write access, and make the URL
above serve that directory. For an existing deployment, copy the old
`public/profile-media` directory into `PROFILE_MEDIA_ROOT` before switching
traffic, preserving the `profile-media/<user-id>/...` paths stored in the user
records.

After changing environment values, clear the cached configuration:

```bash
php artisan config:clear
php artisan config:cache
```

The application stores disk-relative paths in `tbl_users.profile_picture` and
`tbl_users.cover_photo`; Blade generates public URLs from the configured disk.
