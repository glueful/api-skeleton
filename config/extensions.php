<?php

/**
 * Extensions
 *
 * Composer discovers installed `glueful-extension` packages (see their
 * extra.glueful.provider). This file is the single activation allow-list:
 * an installed extension does nothing until its provider FQCN appears below.
 *
 * - Entries are plain string FQCNs (no ::class) so `php glueful extensions:enable|disable`
 *   can edit this list safely. Do not use conditionals/function calls here.
 * - Order is preserved; dependencies are reordered automatically.
 * - Empty = nothing loads. To kill everything fast, set `enabled => []`.
 *
 * Manage with: php glueful extensions:list | enable <name> | disable <name> | cache
 */

return [
    'enabled' => [
        // The first-party user store (identity + account lifecycle). Provides the real
        // UserProviderInterface impl; without it core auth fails closed (NullUserProvider).
        'Glueful\\Extensions\\Users\\UsersServiceProvider',
        // Email delivery channel — registers the 'email' notification channel that Users'
        // forgot-password / email-verification flows send through. Without it those emails no-op.
        'Glueful\\Extensions\\EmailNotification\\EmailNotificationServiceProvider',
        // Rich media processing — image transforms/variants, thumbnails on upload, and
        // media metadata. Binds the framework's MediaProcessorInterface seam.
        'Glueful\\Extensions\\Media\\MediaServiceProvider',
        // Optional RBAC (roles/permissions). Needed only for permission-gated endpoints
        // such as GET /users and GET /users/{uuid} (which require `users.read`). To enable:
        //   composer require glueful/aegis
        //   php glueful extensions:enable aegis
        //   php glueful migrate:run                                    # RBAC tables + seeds default roles
        //   php glueful aegis:bootstrap-admin --user=<uuid-or-email>   # syncs catalog, grants users.read, assigns the role
        // 'Glueful\\Extensions\\Aegis\\Services\\AegisServiceProvider',
    ],
];
