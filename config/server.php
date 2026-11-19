<?php

/*
|--------------------------------------------------------------------------
| Kalendrath server identity
|--------------------------------------------------------------------------
|
| A stable key naming this game server (one server per chapter, see
| docs/etude-serveurs-en-continu.md). It is written next to every item and
| every item operation (origin_server_key, server_key) so that a future
| server merge can tell where an item was born. The Kaelite ledger will read
| the same key.
*/
return [
    'key' => env('SERVER_KEY', 'local'),
];
