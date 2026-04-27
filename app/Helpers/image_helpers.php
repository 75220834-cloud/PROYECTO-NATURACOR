<?php

/*
|--------------------------------------------------------------------------
| Helpers de imágenes — NATURACOR
|--------------------------------------------------------------------------
|
| Funciones globales para resolver la URL pública de una imagen guardada
| en la columna `imagen` de un modelo (típicamente Producto).
|
| Compatibles con dos formatos:
|   1) URL absoluta (Cloudinary u otro CDN): https://res.cloudinary.com/...
|      → se devuelve tal cual.
|   2) Ruta relativa del disco 'public': productos/abc123.jpg
|      → se prefija con asset('storage/...').
|
| Si la imagen está vacía o nula, devuelve null (las vistas pueden mostrar
| el placeholder por defecto).
|
*/

if (! function_exists('producto_image_url')) {
    /**
     * Resuelve la URL pública de una imagen.
     *
     * @param  \App\Models\Producto|object|string|null  $productoOrPath
     *         Acepta el modelo Producto completo, cualquier objeto con propiedad `imagen`, o la cadena directa.
     * @return string|null  URL pública lista para usar en src, o null si no hay imagen.
     */
    function producto_image_url($productoOrPath): ?string
    {
        // Extraer el valor crudo
        if (is_object($productoOrPath)) {
            $imagen = $productoOrPath->imagen ?? null;
        } else {
            $imagen = $productoOrPath;
        }

        if (! is_string($imagen) || trim($imagen) === '') {
            return null;
        }

        // URL absoluta (Cloudinary u otro CDN) → tal cual
        if (str_starts_with($imagen, 'http://') || str_starts_with($imagen, 'https://')) {
            return $imagen;
        }

        // Ruta relativa local → asset('storage/...')
        return asset('storage/' . ltrim($imagen, '/'));
    }
}

if (! function_exists('producto_tiene_imagen')) {
    /**
     * Indica si un producto/objeto tiene imagen registrada (no vacía).
     *
     * @param  \App\Models\Producto|object|string|null  $productoOrPath
     */
    function producto_tiene_imagen($productoOrPath): bool
    {
        return producto_image_url($productoOrPath) !== null;
    }
}