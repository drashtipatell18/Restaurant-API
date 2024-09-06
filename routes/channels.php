<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

Broadcast::channel('chat.{sender_id}.{receiver_id}', function ($user, $sender_id, $receiver_id) {
    // Check if the authenticated user is part of the conversation
    return (int) $user->id === (int) $sender_id || (int) $user->id === (int) $receiver_id;
}, ['guards' => ['api']]);


Broadcast::channel('group.{groupId}', function ($user, $groupId) {
    return $user->groups->contains($groupId);
}, ['guards' => ['api']]);

Broadcast::channel('presence-group.{groupId}', function ($user, $groupId) {
    return $user->groups->contains($groupId);
});


Broadcast::channel('online-users', function ($user) {
    return ['id' => $user->id, 'username' => $user->username, 'status' => $user->status];
});
