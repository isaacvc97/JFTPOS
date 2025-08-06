<?php

if (!function_exists('notify')) {
    function notify(string $type = 'success', string $message = 'Operación realizada') {
        return back()->with('message', ['type' => $type, 'message' => $message]);
    }
}
