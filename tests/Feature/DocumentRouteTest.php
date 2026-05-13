<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentRouteTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'admin']);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_unauthenticated_cannot_access_documento(): void
    {
        $response = $this->get('/documento/contratos/somefile.pdf');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_valid_documento(): void
    {
        Storage::fake('local');
        $path = 'contratos/test-document.pdf';
        Storage::disk('local')->put($path, 'PDF content here');

        $response = $this->actingAs($this->admin)->get("/documento/contratos/{$path}");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_documento_returns_404_for_nonexistent_file(): void
    {
        $response = $this->actingAs($this->admin)->get('/documento/contratos/nonexistent/nonexistent.pdf');
        $response->assertStatus(404);
    }

    public function test_documento_supports_multiple_extensions(): void
    {
        Storage::fake('local');
        $extensions = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png'];

        foreach ($extensions as $ext) {
            $path = "contratos/test.{$ext}";
            Storage::disk('local')->put($path, "content for {$ext}");
            $mime = match ($ext) {
                'pdf' => 'application/pdf',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'doc' => 'application/msword',
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
            };

            $response = $this->actingAs($this->admin)->get("/documento/contratos/{$path}");
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', $mime);
        }
    }

    public function test_documento_inline_disposition(): void
    {
        Storage::fake('local');
        $path = 'contratos/inline-test.pdf';
        Storage::disk('local')->put($path, 'PDF content');

        $response = $this->actingAs($this->admin)->get("/documento/contratos/{$path}");
        $response->assertHeader('Content-Disposition', 'inline; filename="inline-test.pdf"');
    }
}
