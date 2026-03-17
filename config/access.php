<?php

return [
    'roles' => array_values(array_filter(array_map(
        static fn (string $role): string => trim($role),
        explode(',', (string) env('ACCESS_ROLES', 'superadmin,admin,user')),
    ))),

    'manager_roles' => array_values(array_filter(array_map(
        static fn (string $role): string => trim($role),
        explode(',', (string) env('ACCESS_MANAGER_ROLES', 'superadmin')),
    ))),

    'default_role' => (string) env('ACCESS_DEFAULT_ROLE', 'admin'),
];
