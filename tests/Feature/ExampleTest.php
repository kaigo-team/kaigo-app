<?php

test('returns a successful response', function () {
    $response = $this->get(route('kaigo.index'));

    $response->assertStatus(200);
});
