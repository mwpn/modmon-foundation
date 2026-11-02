<?php

declare(strict_types=1);

namespace Tests\Feature\Foundation;

use App\Foundation\Experience\Icons;
use App\Foundation\Experience\Modal;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

/**
 * Contract 1.3.0 (ADR-0011): icon, modal and confirm-delete components.
 */
class ExperienceIconModalTest extends TestCase
{
    /**
     * Simulate the redirect after a failed validation of modal `$modal`.
     *
     * @param  array<string, mixed>  $input
     */
    private function failedSubmit(string $modal, array $input = []): void
    {
        $session = $this->app['session.store'];
        $session->put('_old_input', [Modal::FIELD => $modal] + $input);
        $session->put('errors', (new ViewErrorBag)->put('default', new MessageBag(['name' => ['Nama wajib diisi.']])));
        $this->app['request']->setLaravelSession($session);
        view()->share('errors', $session->get('errors'));
    }

    public function test_icons_are_lucide_files_with_safe_markup(): void
    {
        $files = glob(base_path('app/Foundation/Experience/icons/*.svg'));
        $this->assertGreaterThan(1000, count($files));
        $this->assertFileExists(base_path('app/Foundation/Experience/icons/LICENSE'));

        foreach ($files as $file) {
            $markup = file_get_contents($file);
            preg_match_all('#<\s*/?\s*([a-zA-Z]+)#', $markup, $tags);
            $this->assertSame([], array_diff(array_unique($tags[1]), ['path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse']), basename($file));
            $this->assertDoesNotMatchRegularExpression('/\son\w+=|javascript:|href=/i', $markup, basename($file));
        }

        $this->assertTrue(Icons::has('trash-2'));
        $this->assertFalse(Icons::has('no-such-icon'));
        $this->assertNull(Icons::markup('../LICENSE'));
        $this->assertNull(Icons::markup('Trash'));
    }

    public function test_icon_component_renders_svg_and_nothing_for_unknown_names(): void
    {
        $html = Blade::render('<x-foundation::icon name="pencil" class="h-4 w-4 text-red-500" />');
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $html);
        $this->assertStringContainsString('stroke="currentColor"', $html);
        $this->assertStringContainsString('h-4 w-4 text-red-500', $html);
        $this->assertStringContainsString(Icons::markup('pencil'), $html);

        $this->assertSame('', trim(Blade::render('<x-foundation::icon name="no-such-icon" />')));
    }

    public function test_navigation_item_accepts_an_icon_name_or_raw_svg(): void
    {
        $named = Blade::render('<x-foundation::nav-item label="Siswa" route="/siswa" icon="graduation-cap" />');
        $this->assertStringContainsString(Icons::markup('graduation-cap'), $named);

        $raw = Blade::render('<x-foundation::nav-item label="X" route="/x" icon=\'<svg data-raw="1"></svg>\' />');
        $this->assertStringContainsString('data-raw="1"', $raw);
    }

    public function test_form_modal_renders_closed_with_csrf_method_and_modal_id(): void
    {
        view()->share('errors', new ViewErrorBag);
        $html = Blade::render(
            '<x-foundation::modal id="guru-baru" title="Guru baru" action="/guru/3" method="PUT" submit="Simpan guru"><input name="name"></x-foundation::modal>',
        );

        $this->assertStringContainsString('x-data="{ open: false }"', $html);
        $this->assertStringContainsString('x-teleport="body"', $html);
        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('action="/guru/3"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('name="_method" value="PUT"', $html);
        $this->assertStringContainsString('name="_modal" value="guru-baru"', $html);
        $this->assertStringContainsString('Guru baru', $html);
        $this->assertStringContainsString('Simpan guru', $html);
        $this->assertStringContainsString('<input name="name">', $html);
    }

    public function test_content_modal_has_no_form(): void
    {
        view()->share('errors', new ViewErrorBag);
        $html = Blade::render('<x-foundation::modal id="info">Isi</x-foundation::modal>');

        $this->assertStringNotContainsString('<form', $html);
        $this->assertStringNotContainsString('name="_modal"', $html);
        $this->assertStringContainsString('Isi', $html);
    }

    public function test_only_the_failed_modal_reopens_with_its_old_input_and_error(): void
    {
        $this->failedSubmit('guru-3', ['name' => 'Isian lama']);

        $failed = Blade::render('<x-foundation::modal id="guru-3" action="/guru/3">x</x-foundation::modal>');
        $other = Blade::render('<x-foundation::modal id="guru-4" action="/guru/4">x</x-foundation::modal>');

        $this->assertStringContainsString('x-data="{ open: true }"', $failed);
        $this->assertStringContainsString('Nama wajib diisi.', $failed);
        $this->assertStringContainsString('x-data="{ open: false }"', $other);
        $this->assertStringNotContainsString('Nama wajib diisi.', $other);

        $this->assertTrue(Modal::failed('guru-3'));
        $this->assertFalse(Modal::failed('guru-4'));
        $this->assertSame('Isian lama', Modal::old('guru-3', 'name', 'Asli'));
        $this->assertSame('Asli', Modal::old('guru-4', 'name', 'Asli'));

        // An unticked box is not submitted: the failed modal shows it unticked.
        $this->assertFalse(Modal::checked('guru-3', 'active', true));
        $this->assertTrue(Modal::checked('guru-4', 'active', true));
    }

    public function test_a_redirect_can_reopen_a_modal_after_a_successful_save(): void
    {
        view()->share('errors', new ViewErrorBag);
        $session = $this->app['session.store'];
        $session->put(Modal::REOPEN, 'transaksi-baru');
        $this->app['request']->setLaravelSession($session);

        $this->assertTrue(Modal::opensOnLoad('transaksi-baru'));
        $this->assertFalse(Modal::opensOnLoad('lain'));
        $this->assertStringContainsString('x-data="{ open: true }"', Blade::render('<x-foundation::modal id="transaksi-baru" action="/x">x</x-foundation::modal>'));
        $this->assertStringNotContainsString('alert', Blade::render('<x-foundation::modal id="transaksi-baru" action="/x">x</x-foundation::modal>'), 'no error box');
    }

    public function test_confirm_delete_renders_trigger_and_delete_form(): void
    {
        view()->share('errors', new ViewErrorBag);
        $html = Blade::render('<x-foundation::confirm-delete action="/guru/3" title="Hapus guru?" message="Riwayat tetap ada." class="text-xs" />');

        $id = 'hapus-'.substr(md5('DELETE /guru/3'), 0, 12);
        $this->assertStringContainsString("\$dispatch('open-modal', '{$id}')", html_entity_decode($html));
        $this->assertStringContainsString('name="_method" value="DELETE"', $html);
        $this->assertStringContainsString('action="/guru/3"', $html);
        $this->assertStringContainsString('Hapus guru?', $html);
        $this->assertStringContainsString('Riwayat tetap ada.', $html);
        $this->assertStringContainsString('Ya, hapus', $html);
        $this->assertStringContainsString('bg-error-500', $html);
        $this->assertStringContainsString('text-xs', $html);
    }
}
