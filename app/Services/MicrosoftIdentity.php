<?php

namespace App\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;

/** Verifica firma y claims antes de utilizar la identidad estable. */
class MicrosoftIdentity
{
    public const CONSUMER_TENANT = '9188040d-6c67-4c5b-b112-36a304b66dad';

    public const JWKS_URL = 'https://login.microsoftonline.com/common/discovery/v2.0/keys';

    public function exchange(string $code, string $verifier, string $redirectUri): array
    {
        $tenant = config('services.microsoft.tenant', 'common');
        if (! preg_match('/^(common|organizations|consumers|[a-f0-9-]{36})$/iD', $tenant)) {
            throw new \RuntimeException('Tenant no válido.');
        }

        return $this->request('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token', http_build_query([
            'client_id' => config('services.microsoft.client_id', ''),
            'client_secret' => config('services.microsoft.client_secret', ''),
            'code' => $code, 'code_verifier' => $verifier, 'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]));
    }

    public function verify(string $token, string $nonce): array
    {
        $jwks = Cache::get('microsoft:public-jwks');
        if (! is_array($jwks)) {
            $jwks = $this->refreshKeys();
        }
        try {
            return $this->verifyWithKeys($token, $nonce, $jwks);
        } catch (\UnexpectedValueException $error) {
            // Microsoft puede rotar sus claves. Reintenta una vez con claves actuales.
            return $this->verifyWithKeys($token, $nonce, $this->refreshKeys());
        }
    }

    public function verifyWithKeys(string $token, string $nonce, array $jwks): array
    {
        if (strlen($token) > 16384 || count($parts = explode('.', $token)) !== 3) {
            throw new \UnexpectedValueException('Token inválido.');
        }
        $header = json_decode(JWT::urlsafeB64Decode($parts[0]), true, 16, JSON_THROW_ON_ERROR);
        $hint = json_decode(JWT::urlsafeB64Decode($parts[1]), true, 16, JSON_THROW_ON_ERROR);
        if (($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null) || ! is_string($hint['tid'] ?? null) || ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $hint['tid'])) {
            throw new \UnexpectedValueException('Claims inválidos.');
        }
        $issuer = 'https://login.microsoftonline.com/'.$hint['tid'].'/v2.0';
        $keys = [];
        foreach ($jwks['keys'] ?? [] as $key) {
            if (($key['kty'] ?? null) === 'RSA' && ($key['alg'] ?? 'RS256') === 'RS256' && str_replace('{tenantid}', $hint['tid'], $key['issuer'] ?? '') === $issuer) {
                $key['alg'] = 'RS256';
                $keys[] = $key;
            }
        }
        if (! $keys) {
            throw new \UnexpectedValueException('Emisor de clave inválido.');
        }
        $claims = (array) JWT::decode($token, JWK::parseKeySet(['keys' => $keys], 'RS256'));
        $client = config('services.microsoft.client_id', '');
        $tenant = config('services.microsoft.tenant', 'common');
        if (! $client || ($claims['aud'] ?? null) !== $client || ($claims['iss'] ?? null) !== $issuer || ! is_string($claims['nonce'] ?? null) || ! hash_equals($nonce, $claims['nonce']) || ! is_numeric($claims['exp'] ?? null) || (int) $claims['exp'] <= time() || ! is_string($claims['oid'] ?? null) || ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/iD', $claims['oid'])) {
            throw new \UnexpectedValueException('Identidad no válida para esta aplicación.');
        }
        if (! in_array($tenant, ['common', 'organizations', 'consumers'], true) && strcasecmp($tenant, $claims['tid']) !== 0) {
            throw new \UnexpectedValueException('Tenant no autorizado.');
        }
        $isPersonal = strcasecmp($claims['tid'], self::CONSUMER_TENANT) === 0;
        if (($tenant === 'organizations' && $isPersonal) || ($tenant === 'consumers' && ! $isPersonal)) {
            throw new \UnexpectedValueException('Tipo de cuenta no autorizado.');
        }

        return $claims;
    }

    /** Datos de contacto de una identidad que ya pasó la validación del token. */
    public function registrationProfile(array $claims, string $accessToken): array
    {
        if (strcasecmp($claims['tid'], self::CONSUMER_TENANT) === 0) {
            // Las cuentas personales pueden usar el perfil del token sin consultar Graph.
            // El correo es solo de contacto: la vinculación usa siempre tid y oid.
            $email = null;
            foreach ([$claims['email'] ?? null, $claims['preferred_username'] ?? null] as $candidate) {
                if (is_string($candidate) && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                    $email = $candidate;
                    break;
                }
            }

            return ['id' => $claims['oid'], 'mail' => $email,
                'displayName' => $claims['name'] ?? 'Alumno',
                'givenName' => $claims['given_name'] ?? $claims['name'] ?? 'Alumno',
                'surname' => $claims['family_name'] ?? ''];
        }

        $profile = $this->profile($accessToken);
        if (! is_string($profile['id'] ?? null) || strcasecmp($profile['id'], $claims['oid']) !== 0) {
            throw new \UnexpectedValueException('El perfil Microsoft no corresponde a la identidad validada.');
        }

        return $profile;
    }

    public function profile(string $accessToken): array
    {
        return $this->request('https://graph.microsoft.com/v1.0/me', null, ['Authorization: Bearer '.$accessToken, 'Accept: application/json']);
    }

    private function refreshKeys(): array
    {
        $keys = $this->request(self::JWKS_URL);
        Cache::put('microsoft:public-jwks', $keys, 3600);

        return $keys;
    }

    protected function request(string $url, ?string $post = null, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 20, CURLOPT_FOLLOWLOCATION => false]);
        // OpenSSL en Windows puede no tener un archivo CA configurado en php.ini.
        // Usa el almacén de confianza del sistema sin desactivar TLS ni validar menos.
        if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA') && version_compare(curl_version()['version'], '7.71.0', '>=')) {
            curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
        }
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $curlCode = curl_errno($ch);
        unset($ch);
        if (! is_string($body) || $status !== 200) {
            $errorResponse = is_string($body) ? json_decode($body, true) : null;
            $oauthError = $errorResponse['error'] ?? '';
            if (! is_string($oauthError) || ! preg_match('/^[a-z_]{1,50}$/D', $oauthError)) {
                $oauthError = '';
            }
            throw new MicrosoftTransportException($curlCode, $status, $oauthError);
        }
        $json = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        if (! is_array($json)) {
            throw new \RuntimeException('Respuesta Microsoft inválida.');
        }

        return $json;
    }
}
