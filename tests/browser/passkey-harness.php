<?php

use Gibbon\Auth\Passkey\PasskeyRepository;
use Gibbon\Auth\Passkey\PasskeyService;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\User\UserGateway;
use Webauthn\PublicKeyCredentialUserEntity;

function __($value)
{
    return $value;
}

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

class PasskeyBrowserSession implements Session
{
    private array $data;

    public function __construct(private string $path)
    {
        $this->data = is_file($path)
            ? json_decode((string) file_get_contents($path), true) ?: []
            : [];
    }

    public function exists($keys)
    {
        foreach ((array) $keys as $key) {
            if (!array_key_exists($key, $this->data)) {
                return false;
            }
        }

        return true;
    }

    public function has($keys)
    {
        foreach ((array) $keys as $key) {
            if (!isset($this->data[$key])) {
                return false;
            }
        }

        return true;
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    public function remove($key)
    {
        $value = $this->data[$key] ?? null;
        unset($this->data[$key]);

        return $value;
    }

    public function forget($keys)
    {
        foreach ((array) $keys as $key) {
            unset($this->data[$key]);
        }
    }

    public function save()
    {
        file_put_contents($this->path, json_encode($this->data));
    }
}

class PasskeyBrowserRepository extends PasskeyRepository
{
    public function __construct()
    {
    }

    public function findAllForUserEntity(PublicKeyCredentialUserEntity $user): array
    {
        return [];
    }
}

class PasskeyBrowserUserGateway extends UserGateway
{
    public function __construct()
    {
    }

    public function getByID($primaryKeyValue, $cols = []): array
    {
        return [
            'gibbonPersonID' => '0000000001',
            'username'       => 'browser-test',
            'preferredName'  => 'Browser',
            'surname'        => 'Test',
        ];
    }
}

$mode = $argv[1] ?? '';
$sessionPath = $argv[2] ?? '';
$origin = $argv[3] ?? '';
if (!in_array($mode, ['options', 'verify'], true) || $sessionPath === '' || $origin === '') {
    fwrite(STDERR, "usage: passkey-harness.php options|verify <session-file> <origin>\n");
    exit(2);
}

$session = new PasskeyBrowserSession($sessionPath);
$session->set('absoluteURL', $origin);
$session->set('systemName', 'Gibbon Passkey Test');
$service = new PasskeyService(
    new PasskeyBrowserRepository(),
    $session,
    new PasskeyBrowserUserGateway()
);

try {
    if ($mode === 'options') {
        $result = $service->createCreationOptions(1);
    } else {
        $payload = json_decode((string) stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
        $credential = $service->verifyAttestation(
            (array) ($payload['response'] ?? []),
            (string) ($payload['nonce'] ?? ''),
            1
        );
        $result = [
            'credentialId' => bin2hex($credential->publicKeyCredentialId),
            'personID'     => $credential->userHandle,
        ];
    }

    $session->save();
    echo json_encode($result, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage()."\n");
    exit(1);
}
