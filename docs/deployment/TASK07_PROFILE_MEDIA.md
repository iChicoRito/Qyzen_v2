# Task 07: Profile media storage

Profile pictures and cover photos are stored directly in `public/profile-media`
on the `profile_media` local disk. No extra profile-media environment variables
are required.

On production, make sure the `public/profile-media` directory exists and is
writable by the web/PHP user. The application stores disk-relative paths in
`tbl_users.profile_picture` and `tbl_users.cover_photo`, and Blade renders them
under `APP_URL/profile-media/...`.
