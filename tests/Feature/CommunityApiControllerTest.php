<?php

use App\Enums\KnowiiCommunityVisibility;
use App\Models\User;
use Illuminate\Http\Response;

test('communities can be created via the API', function () {
    $this->actingAs($user = User::factory()->withUserProfile()->withPersonalCommunity()->create());

    $input = [
        'name' => 'Test Community',
        'description' => 'Awesome community',
        'visibility' => KnowiiCommunityVisibility::Public->value,
    ];

    $requestUrl = 'api/v1/communities';

    // TODO stop hardcoding URLs in tests
    $response = $this->json('POST', $requestUrl, $input, [
        'Accept' => 'application/json',
    ]);

    $response->assertStatus(Response::HTTP_CREATED);

    expect($user->ownedCommunities)->toHaveCount(2);
    expect($user->ownedCommunities()->latest('id')->first()->name)->toEqual('Test Community');
    expect($user->ownedCommunities()->latest('id')->first()->slug)->toEqual('test-community');
    expect($user->ownedCommunities()->latest('id')->first()->description)->toEqual('Awesome community');
    expect($user->ownedCommunities()->latest('id')->first()->visibility)->toEqual(KnowiiCommunityVisibility::Public);
});

test('communities can be deleted via the API', function () {
    $this->actingAs($user = User::factory()->withUserProfile()->withPersonalCommunity()->create());

    $requestUrl = 'api/v1/communities/'.$user->ownedCommunities()->latest('id')->first()->cuid;

    // TODO stop hardcoding URLs in tests
    $response = $this->delete($requestUrl, [
        'Accept' => 'application/json',
    ]);

    $response->assertStatus(Response::HTTP_NO_CONTENT);

    expect($user->ownedCommunities)->toHaveCount(0);
});
