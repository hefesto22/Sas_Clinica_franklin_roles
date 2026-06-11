<?php

it('redirige la raíz al login del panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/admin/login');
});
