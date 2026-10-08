<?php

return [
    // The seeded admin account's password (database/seeders/AdminUserSeeder.php).
    // There's no in-app way to change an admin password, so this is how it's
    // managed: set it on the server, redeploy, and the seeder (run on every
    // boot by the Dockerfile) applies it. Use a long random value.
    'password' => env('ADMIN_PASSWORD'),
];
