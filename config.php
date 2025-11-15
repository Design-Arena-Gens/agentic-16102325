<?php
declare(strict_types=1);

// Database connection settings; adjust per Hostinger deployment.
const DB_HOST = 'localhost';
const DB_NAME = 'construction_erp';
const DB_USER = 'db_user';
const DB_PASS = 'db_password';

// File system paths.
const BASE_PATH = __DIR__;
const UPLOAD_PATH = BASE_PATH . '/uploads';
const DPR_PHOTO_PATH = BASE_PATH . '/dpr_photos';

// Application defaults.
const PASSWORD_COST = 12;
const MAX_UPLOAD_BYTES = 5 * 1024 * 1024; // 5 MB cap per upload.
const COMPRESSED_IMAGE_QUALITY = 75;

// Timezone for consistent timestamps.
date_default_timezone_set('UTC');
