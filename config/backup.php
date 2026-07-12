<?php

return [
    'storage_path' => env('BACKUP_STORAGE_PATH'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
];
