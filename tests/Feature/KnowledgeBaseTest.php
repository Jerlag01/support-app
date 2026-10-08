<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_articles_are_listed_searched_and_shown_in_the_visitor_language(): void
    {
        $category = FaqCategory::factory()->create(['name' => ['nl' => 'Laadpalen', 'fr' => 'Bornes de recharge']]);
        $faq = Faq::factory()->for($category, 'category')->create([
            'question' => ['nl' => 'Hoe reset ik mijn laadpaal?', 'fr' => 'Comment réinitialiser ma borne ?'],
            'answer' => ['nl' => 'Zet de automaat 10 seconden uit.'],
        ]);

        $this->get('/kb')->assertOk()->assertSee('Laadpalen')->assertSee('Hoe reset ik mijn laadpaal?');
        $this->get('/kb?search=reset')->assertSee('1 resultaat');
        $this->get('/kb?search=zonnepaneel')->assertSee('Geen resultaten');
        $this->get(route('kb.category', $category))->assertOk()->assertSee('Zet de automaat');
        $this->get(route('kb.show', $faq))->assertOk()->assertSee('Zet de automaat');

        // French title, Dutch answer as fallback.
        $this->get('/language/fr');
        $this->get(route('kb.show', $faq))->assertSee('Comment réinitialiser ma borne ?')->assertSee('Zet de automaat');
    }

    public function test_unpublished_articles_and_private_categories_stay_hidden(): void
    {
        $private = FaqCategory::factory()->create(['is_public' => false]);
        $internalFaq = Faq::factory()->for($private, 'category')->create(['question' => ['nl' => 'Interne procedure']]);
        $draft = Faq::factory()->create(['is_published' => false, 'question' => ['nl' => 'Nog niet klaar']]);

        $this->get('/kb')->assertDontSee('Interne procedure')->assertDontSee('Nog niet klaar');
        $this->get(route('kb.show', $internalFaq))->assertNotFound();
        $this->get(route('kb.show', $draft))->assertNotFound();
        $this->get(route('kb.category', $private))->assertNotFound();
    }
}
