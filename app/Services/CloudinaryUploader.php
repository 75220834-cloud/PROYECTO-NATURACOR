<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Servicio único de subida de imágenes para NATURACOR.
 *
 * Estrategia:
 * - Si está configurada CLOUDINARY_URL en .env → sube a Cloudinary y devuelve la URL absoluta (https://res.cloudinary.com/...).
 * - Si NO está configurada → cae al disco 'public' local y devuelve la ruta relativa (productos/abc123.jpg) — comportamiento idéntico al original.
 *
 * El valor devuelto por upload() es lo que se debe guardar en la columna `imagen` del modelo.
 * El helper producto_image_url() sabe interpretar ambos formatos (URL absoluta o ruta relativa).
 */
class CloudinaryUploader
{
    /**
     * Sube una imagen y devuelve la cadena que debe persistirse en la columna `imagen`.
     *
     * @param  UploadedFile  $file  Archivo subido por el usuario (validado previamente como imagen).
     * @param  string|null   $oldValue  Valor anterior de `imagen` (para borrarlo si corresponde tras una subida exitosa).
     * @return string  URL absoluta de Cloudinary o ruta relativa del disco 'public'.
     */
    public function upload(UploadedFile $file, ?string $oldValue = null): string
    {
        if ($this->isCloudinaryEnabled()) {
            try {
                $url = $this->uploadToCloudinary($file);
                $this->deleteOld($oldValue); // si la anterior era local, la borramos del disco
                return $url;
            } catch (\Throwable $e) {
                // Fallback silencioso: si Cloudinary falla por red/credenciales, no rompemos al usuario.
                Log::warning('Cloudinary upload falló, usando fallback local.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Modo local (sin Cloudinary configurado o tras fallback)
        $path = $file->store('productos', 'public');
        $this->deleteOld($oldValue);
        return $path;
    }

    /**
     * Borra el valor anterior si existe y es local (no toca URLs de Cloudinary,
     * ya que Cloudinary cobra por API destroy y no es crítico en este caso).
     */
    public function deleteOld(?string $oldValue): void
    {
        if (empty($oldValue)) {
            return;
        }

        // Si es URL absoluta (Cloudinary u otra), no la tocamos desde aquí.
        if (str_starts_with($oldValue, 'http://') || str_starts_with($oldValue, 'https://')) {
            return;
        }

        try {
            Storage::disk('public')->delete($oldValue);
        } catch (\Throwable $e) {
            Log::info('No se pudo borrar imagen local previa.', [
                'path' => $oldValue,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ¿Está Cloudinary configurado y disponible en este entorno?
     */
    public function isCloudinaryEnabled(): bool
    {
        $url = config('services.cloudinary.url');
        return is_string($url) && str_starts_with($url, 'cloudinary://');
    }

    /**
     * Sube efectivamente a Cloudinary y devuelve la secure_url.
     */
    protected function uploadToCloudinary(UploadedFile $file): string
    {
        $cloudinary = new Cloudinary(config('services.cloudinary.url'));
        $folder = config('services.cloudinary.folder', 'naturacor/productos');

        $result = $cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => $folder,
                'resource_type' => 'image',
                'use_filename' => false,
                'unique_filename' => true,
            ]
        );

        if (empty($result['secure_url'])) {
            throw new \RuntimeException('Cloudinary no devolvió secure_url.');
        }

        return (string) $result['secure_url'];
    }
}