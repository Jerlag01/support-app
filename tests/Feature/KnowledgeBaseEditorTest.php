<?php

namespace Tests\Feature;

use App\Enums\FaqFormat;
use App\Filament\Agent\Resources\Faqs\Pages\CreateFaq;
use App\Filament\Agent\Resources\Faqs\Pages\EditFaq;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KnowledgeBaseEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_markdown_answers_render_without_raw_html(): void
    {
        $faq = Faq::factory()->create([
            'format' => FaqFormat::Markdown,
            'answer' => ['nl' => "## Stappen\n\n1. Zet de **automaat** uit\n2. Wacht\n\n<script>alert(1)</script>\n\n[gevaarlijk](javascript:alert(1))"],
        ]);

        $html = $faq->renderedAnswer()->toHtml();

        $this->assertStringContainsString('<h2>Stappen</h2>', $html);
        $this->assertStringContainsString('<strong>automaat</strong>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->get(route('kb.show', $faq))->assertOk()->assertSee('<strong>automaat</strong>', false);
    }

    public function test_rich_text_answers_render_and_drop_unknown_markup(): void
    {
        $faq = Faq::factory()->create([
            'format' => FaqFormat::RichText,
            'answer' => ['nl' => '<p>Zet de <strong>automaat</strong> uit.</p><script>alert(1)</script><p onclick="alert(1)">Klaar</p>'],
        ]);

        $html = $faq->renderedAnswer()->toHtml();

        $this->assertStringContainsString('<strong>automaat</strong>', $html);
        $this->assertStringContainsString('Klaar', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onclick', $html);
    }

    public function test_agent_writes_a_markdown_article_with_downloads(): void
    {
        $this->actingAs(User::factory()->agent()->create());
        Filament::setCurrentPanel('agent');
        $category = FaqCategory::factory()->create();

        Livewire::test(CreateFaq::class)
            ->fillForm([
                'faq_category_id' => $category->id,
                'question' => ['nl' => 'Waar vind ik de handleiding?'],
                'format' => FaqFormat::Markdown->value,
                'answer' => ['nl' => 'Zie **bijlage**.'],
                'attachments' => [UploadedFile::fake()->create('handleiding.pdf', 120, 'application/pdf')],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $faq = Faq::sole();
        $this->assertSame(FaqFormat::Markdown, $faq->format);
        $this->assertSame('Zie **bijlage**.', $faq->translate('answer'));
        $this->assertSame('handleiding.pdf', $faq->downloads()[0]['name']);

        $this->get(route('kb.show', $faq))
            ->assertSee('<strong>bijlage</strong>', false)
            ->assertSee('handleiding.pdf')
            ->assertSee($faq->downloads()[0]['url']);
    }

    public function test_removed_downloads_are_deleted_from_disk(): void
    {
        Storage::disk('public')->put('kb/files/oud.pdf', 'x');
        Storage::disk('public')->put('kb/files/blijft.pdf', 'x');
        $faq = Faq::factory()->create([
            'attachments' => ['kb/files/oud.pdf', 'kb/files/blijft.pdf'],
            'attachment_names' => ['kb/files/oud.pdf' => 'oud.pdf', 'kb/files/blijft.pdf' => 'blijft.pdf'],
        ]);

        $this->actingAs(User::factory()->agent()->create());
        Filament::setCurrentPanel('agent');
        Livewire::test(EditFaq::class, ['record' => $faq->getRouteKey()])
            ->fillForm(['attachments' => ['kb/files/blijft.pdf']])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('kb/files/oud.pdf');
        Storage::disk('public')->assertExists('kb/files/blijft.pdf');

        $faq->refresh()->delete();
        Storage::disk('public')->assertMissing('kb/files/blijft.pdf');
    }

    public function test_search_looks_inside_formatted_answers(): void
    {
        Faq::factory()->create([
            'question' => ['nl' => 'Algemene vraag'],
            'format' => FaqFormat::Markdown,
            'answer' => ['nl' => 'Contacteer de **storingsdienst**.'],
        ]);

        $this->get('/kb?search=storingsdienst')->assertSee('Algemene vraag');
    }
}
