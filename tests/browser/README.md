# Passkey browser test

The virtual-authenticator test exercises a real WebAuthn registration ceremony in
headless Chromium and verifies the resulting attestation with the server-side
library:

```sh
node tests/browser/passkey-virtual-authenticator.mjs
```

It does not require a Gibbon database or application login. The PHP harness uses
an isolated session file, a read-only repository stub and a single test user.

Override `CHROMIUM_BINARY` or `PHP_BINARY` when the executables are installed
outside their default paths.
