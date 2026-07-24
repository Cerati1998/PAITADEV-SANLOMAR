<?php

namespace Deployer;

require 'recipe/laravel.php';

// ── Configuracion del proyecto ──────────────────────────────────────────
set('application', 'sanlomar');
set('repository', 'git@github.com:Cerati1998/PAITADEV-SANLOMAR.git');
set('branch', 'featured/sanlomar');
set('keep_releases', 5);

// Archivos/carpetas compartidos entre releases (no se pisan en cada deploy).
set('shared_files', ['.env']);
set('shared_dirs', ['storage']);
set('writable_dirs', ['bootstrap/cache', 'storage']);

// wkhtmltopdf y otros paths de storage/certificados viven fuera del release,
// dentro de shared/storage — ver guia-transportista.md y CLAUDE.md.

// ── Host ─────────────────────────────────────────────────────────────────
host('vps-sanlomar')
    ->setHostname('174.138.77.217')
    ->setRemoteUser('inspectec-sanlomar')
    // Ruta dedicada para las releases atomicas de Deployer (releases/ shared/ current/).
    // Distinta de la carpeta actual (sanlomar.inspectec.cloud) para no chocar con el
    // deploy manual existente hasta que el vhost de CloudPanel apunte a current/public.
    ->set('deploy_path', '/home/inspectec-sanlomar/htdocs/sanlomar.inspectec.cloud-releases');

// ── Build de frontend (Vite) ────────────────────────────────────────────
desc('Instala dependencias de Node y compila assets');
task('npm:build', function () {
    run('cd {{release_path}} && npm ci');
    run('cd {{release_path}} && npm run build');
});

// ── Hooks ────────────────────────────────────────────────────────────────
after('deploy:vendors', 'npm:build');
after('deploy:failed', 'deploy:unlock');

// deploy:cleanup deja solo las ultimas {{keep_releases}} releases, ya
// incluido por recipe/laravel.php.
