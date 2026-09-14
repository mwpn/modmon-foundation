<?php

declare(strict_types=1);

namespace Modules\Branding\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Branding\Application\Services\ApplicationBranding;
use Modules\Branding\Domain\Contracts\BrandingContract;
use Modules\Branding\Domain\DTOs\BrandingData;

final class BrandingController extends Controller
{
    public function __construct(
        private readonly BrandingContract $branding,
    ) {}

    public function edit(): View
    {
        return view('branding::edit', [
            'branding' => $this->branding->current(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'accent_color' => ['nullable', 'string', 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/'],
            'login_title' => ['nullable', 'string', 'max:255'],
            'login_subtitle' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico,jpg,jpeg,gif,webp', 'max:512'],
            'clear_logo' => ['sometimes', 'boolean'],
            'clear_logo_dark' => ['sometimes', 'boolean'],
            'clear_favicon' => ['sometimes', 'boolean'],
        ]);

        try {
            $logoPath = $this->storeUpload($request->file('logo'), 'logo');
            $logoDarkPath = $this->storeUpload($request->file('logo_dark'), 'logo-dark');
            $faviconPath = $this->storeUpload($request->file('favicon'), 'favicon');

            $this->branding->update(new BrandingData(
                name: $data['name'],
                logoPath: $logoPath,
                logoDarkPath: $logoDarkPath,
                faviconPath: $faviconPath,
                primaryColor: $data['primary_color'] ?? null,
                accentColor: $data['accent_color'] ?? null,
                loginTitle: $data['login_title'] ?? null,
                loginSubtitle: $data['login_subtitle'] ?? null,
                clearLogo: $request->boolean('clear_logo'),
                clearLogoDark: $request->boolean('clear_logo_dark'),
                clearFavicon: $request->boolean('clear_favicon'),
            ));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('branding.edit')
            ->with('status', 'Branding saved.');
    }

    private function storeUpload(?UploadedFile $file, string $basename): ?string
    {
        if ($file === null) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $filename = $basename.'.'.$extension;
        $directory = ApplicationBranding::ASSET_DIRECTORY;

        Storage::disk('public')->makeDirectory($directory);

        $path = $file->storeAs($directory, $filename, 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                $basename === 'logo-dark' ? 'logo_dark' : $basename => 'Failed to store uploaded branding asset.',
            ]);
        }

        return $path;
    }
}
