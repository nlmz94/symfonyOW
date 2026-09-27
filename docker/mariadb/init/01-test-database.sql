-- config/packages/doctrine.yaml appends `_test` to the dbname under APP_ENV=test.
-- The app user cannot create schemas, so provision it up front.
CREATE DATABASE IF NOT EXISTS `onlyweebs_test`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `onlyweebs_test`.* TO 'app'@'%';
FLUSH PRIVILEGES;
