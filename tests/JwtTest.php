<?php
use PHPUnit\Framework\TestCase;

require_once APPPATH . 'libraries/Jwt.php';

/**
 * Validates the in-house JWT (HS256) library:
 *  - round-trips a payload,
 *  - rejects the `alg: none` downgrade attack,
 *  - rejects expired / wrong-issuer / signature-mangled tokens,
 *  - extracts a bearer header correctly.
 */
class JwtTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        putenv('SECURITY_KEY=ci_test_key_for_jwt_0000000000000000000000000000000');
        putenv('JWT_SECRET=');
    }

    public function test_round_trip()
    {
        $jwt = new Jwt();
        $token = $jwt->encode(['sub' => 42, 'role' => 1, 'scope' => 'read:self'], 900);
        $claims = $jwt->decode($token);
        $this->assertSame(42, $claims['sub']);
        $this->assertSame(1, $claims['role']);
        $this->assertSame('read:self', $claims['scope']);
        $this->assertSame('access', $claims['type']);
        $this->assertArrayHasKey('jti', $claims);
    }

    public function test_rejects_alg_none()
    {
        $jwt = new Jwt();
        // Forge a token with alg=none + a payload claiming superuser scope.
        $header  = rtrim(strtr(base64_encode(json_encode(['alg' => 'none', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = rtrim(strtr(base64_encode(json_encode([
            'sub' => 1, 'role' => 1, 'scope' => '*',
            'iss' => 'http://localhost/', 'exp' => time() + 600, 'jti' => 'x',
        ])), '+/', '-_'), '=');
        $token = $header . '.' . $payload . '.';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unsupported algorithm/i');
        $jwt->decode($token);
    }

    public function test_rejects_tampered_signature()
    {
        $jwt = new Jwt();
        $token = $jwt->encode(['sub' => 99], 600);
        $parts = explode('.', $token);
        // Flip the last character of the signature to a guaranteed-different one.
        // (A strtr('A','B') swap is not reliable here: signatures are random per
        // run, so a signature containing no 'A' would be left intact and still verify.)
        $sig      = $parts[2];
        $last     = substr($sig, -1);
        $parts[2] = substr($sig, 0, -1) . ($last === 'x' ? 'y' : 'x');
        $tampered = implode('.', $parts);

        $this->expectException(RuntimeException::class);
        $jwt->decode($tampered);
    }

    public function test_rejects_expired_token()
    {
        $jwt = new Jwt();
        // 1-second TTL, then wait past the leeway.
        $token = $jwt->encode(['sub' => 99], 1);
        sleep(2);
        // The library has a 30s leeway, but for the assertion we use reflection
        // is overkill — just verify the helper sees expiry semantics correctly
        // by crafting an expired token manually.
        $expired = $jwt->encode(['sub' => 99], 1);
        // Build a JWT with exp far in the past via reflection on the library.
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = [
            'sub' => 99, 'iss' => 'http://localhost/', 'iat' => time() - 7200,
            'nbf' => time() - 7200, 'exp' => time() - 3600, 'jti' => 'x', 'type' => 'access',
        ];
        $payload_b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $key = getenv('SECURITY_KEY');
        $sig = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $header . '.' . $payload_b64, $key, true)
        ), '+/', '-_'), '=');
        $hand_rolled = $header . '.' . $payload_b64 . '.' . $sig;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/expired/i');
        $jwt->decode($hand_rolled);
    }

    public function test_rejects_wrong_issuer()
    {
        $jwt = new Jwt();
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $payload = [
            'sub' => 1, 'iss' => 'https://evil.example/', 'iat' => time(),
            'nbf' => time(), 'exp' => time() + 600, 'jti' => 'x', 'type' => 'access',
        ];
        $payload_b64 = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
        $key = getenv('SECURITY_KEY');
        $sig = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $header . '.' . $payload_b64, $key, true)
        ), '+/', '-_'), '=');
        $token = $header . '.' . $payload_b64 . '.' . $sig;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/issuer/i');
        $jwt->decode($token);
    }

    public function test_extract_bearer()
    {
        $this->assertSame('abc.def.ghi', Jwt::extract_bearer('Bearer abc.def.ghi'));
        $this->assertNull(Jwt::extract_bearer(null));
        $this->assertNull(Jwt::extract_bearer(''));
        $this->assertNull(Jwt::extract_bearer('Basic dXNlcjpwYXNz'));
        $this->assertNull(Jwt::extract_bearer('Bearer with spaces'));
    }

    public function test_short_key_throws()
    {
        putenv('SECURITY_KEY=tiny');
        putenv('JWT_SECRET=tiny');
        try {
            new Jwt();
            $this->fail('Expected RuntimeException for short key');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('32 bytes', $e->getMessage());
        } finally {
            putenv('SECURITY_KEY=ci_test_key_for_jwt_0000000000000000000000000000000');
            putenv('JWT_SECRET=');
        }
    }
}
