<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_start_navigation_opens_login_instead_of_the_student_portal(): void
    {
        foreach (['/', '/?r=site/index', '/index.php?r=site/index', '/frontend/web/index.php?r=site/index', '/backend/web/index.php?r=site/index', '/inicio'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }

        $page = $this->get('/login')->assertOk();
        $document = new \DOMDocument;
        $document->loadHTML($page->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $links = (new \DOMXPath($document))->query('//nav//a[contains(concat(" ", normalize-space(@class), " "), " modelo-brand ") or normalize-space(.)="Inicio"]');
        $this->assertCount(2, $links);
        foreach ($links as $link) {
            $target = $link->getAttribute('href');
            $this->assertSame(route('login'), $target);
            $this->get($target)->assertOk()->assertViewIs('login')
                ->assertSee('name="username"', false)->assertSee('name="password"', false)
                ->assertDontSee('Detección de Riesgos y Salud Estudiantil');
        }
    }
}
