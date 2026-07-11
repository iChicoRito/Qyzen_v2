# Task 07: Profile media storage

Profile pictures and cover photos are stored on the `profile_media` local disk
outside the deployed repository so redeploys do not delete uploads.

Set this value in the server's `.env`:

```dotenv
PROFILE_MEDIA_ROOT=/home/<user>/profile-media
```

Create that directory and grant the web/PHP user write access. The application
serves files through Laravel at `APP_URL/profile-media/...`, so no web-server
alias or public symlink is required. The application stores disk-relative paths
in `tbl_users.profile_picture` and `tbl_users.cover_photo`.
