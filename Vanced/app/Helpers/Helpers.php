<?php
// app/Helpers/helpers.php
namespace App\Helpers;


class Helpers
{
    public static function message($type, $message)
    {
        return [
            'type' => $type,
            'message' => $message
        ];
    }
}