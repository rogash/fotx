<?php

namespace Tests\Feature\Fotx;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_not_found_page_links_buyers_to_site_home(): void
    {
        $this->get('/pagina-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Não encontramos esta página')
            ->assertSee('href="https://fotx.com.br/"', false)
            ->assertDontSee('para-voce.html', false);
    }
}
