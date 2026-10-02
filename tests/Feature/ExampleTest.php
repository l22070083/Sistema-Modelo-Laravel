<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_displays_student_portal(): void
    {
        $this->get('/')->assertOk()->assertSee('Detección de Riesgos y Salud Estudiantil');
    }
}
