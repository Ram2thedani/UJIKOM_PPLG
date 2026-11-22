<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $uploadedLogo = 'C:/Users/DICOMMA !!/.gemini/antigravity/brain/135686b4-ad4e-429a-93e4-0a9da6d84751/.user_uploaded/media_1794968305429_474e5eb4.webp';

        if (is_file($uploadedLogo)) {
            $backendLogo = public_path('logo-marhas.webp');
            if (! is_file($backendLogo)) {
                @copy($uploadedLogo, $backendLogo);
            }

            $frontendPublicDir = base_path('../frontend/public');
            if (! is_dir($frontendPublicDir)) {
                @mkdir($frontendPublicDir, 0755, true);
            }
            $frontendLogo = $frontendPublicDir.'/logo-marhas.webp';
            if (! is_file($frontendLogo)) {
                @copy($uploadedLogo, $frontendLogo);
            }
        }
    }
}
