<?php

namespace App\Services;

/** Diagnóstico de transporte sin tokens, secretos ni respuestas completas. */
class MicrosoftTransportException extends \RuntimeException
{
    public function __construct(public readonly int $curlCode, public readonly int $httpStatus, public readonly string $oauthError = '')
    {
        parent::__construct('Microsoft no devolvió una respuesta válida por HTTPS.', $curlCode ?: $httpStatus);
    }
}
