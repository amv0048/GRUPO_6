<?php
require_once __DIR__ . '/url.php';

function media_normalize_url(?string $path, ?string $fallback = null): string
{
    $path = trim((string)$path);

    if ($path === '') {
        return $fallback !== null ? media_normalize_url($fallback) : '';
    }

    $path = str_replace('\\', '/', $path);

    if (preg_match('#^(https?:)?//#i', $path) || str_starts_with($path, 'data:')) {
        return $path;
    }

    while (str_starts_with($path, '../')) {
        $path = substr($path, 3);
    }

    if (str_starts_with($path, './')) {
        $path = substr($path, 2);
    }

    return app_url($path);
}

function media_storage_path(?string $path): ?string
{
    $normalized = media_normalize_url($path);
    if ($normalized === '' || preg_match('#^(https?:)?//#i', $normalized) || str_starts_with($normalized, 'data:')) {
        return null;
    }

    $relative = ltrim($normalized, '/');
    if (str_starts_with($relative, 'public/')) {
        $relative = substr($relative, 7);
    }

    $root = realpath(__DIR__ . '/../../');
    if ($root === false) {
        return null;
    }

    return $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

function media_img_root(): string
{
    $root = realpath(__DIR__ . '/../../img');
    return $root !== false ? $root : __DIR__ . '/../../img';
}

function media_animal_dir(int $id_protectora, int $id_animal): string
{
    return media_img_root() . '/protectoras/protectora_' . $id_protectora . '/animal_' . $id_animal;
}

function media_animal_url(int $id_protectora, int $id_animal, string $archivo): string
{
    return '/img/protectoras/protectora_' . $id_protectora . '/animal_' . $id_animal . '/' . $archivo;
}

function media_user_profile_dir(int $id_usuario): string
{
    return media_img_root() . '/profile/usuarios/user_' . $id_usuario;
}

function media_user_profile_url(int $id_usuario, string $archivo): string
{
    return '/img/profile/usuarios/user_' . $id_usuario . '/' . $archivo;
}

function media_protectora_profile_dir(int $id_protectora): string
{
    return media_img_root() . '/protectoras/protectora_' . $id_protectora . '/foto_perfil';
}

function media_protectora_profile_url(int $id_protectora, string $archivo): string
{
    return '/img/protectoras/protectora_' . $id_protectora . '/foto_perfil/' . $archivo;
}
