<?php

function censurarComentario(string $texto): string
{
    $palabrasProhibidas = [
        'chingada',
        'chingado',
        'pendejo',
        'pendeja',
        'cabron',
        'cabrón',
        'mierda',
        'puto',
        'puta',
        'verga',
        'idiota',
        'xxx',
        'puñetas',
        'sexo',
        'estupido'.
        'estupida'
    ];

    foreach ($palabrasProhibidas as $palabra) {
        $patron = '/(?<![\p{L}\p{N}])'
            . preg_quote($palabra, '/')
            . '(?![\p{L}\p{N}])/iu';

        $texto = preg_replace_callback(
            $patron,
            function ($coincidencia) {
                return str_repeat(
                    '*',
                    mb_strlen($coincidencia[0], 'UTF-8')
                );
            },
            $texto
        );
    }

    return $texto;
}
