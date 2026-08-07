# Browser Tests

End-to-end browser tests for Jitsi Admin using Playwright via Pest's browser plugin.
The tests run against a live DDEV environment with real Keycloak OIDC authentication.

## Software Stack

| Layer              | Technology                                                   |
|--------------------|--------------------------------------------------------------|
| Test framework     | [Pest PHP](https://pestphp.com/) v4                          |
| Browser automation | [Playwright](https://playwright.dev/) (npm package v1.62)    |
| Browser engine     | Chromium (headless or headed)                                |
| PHP integration    | [pestphp/pest-plugin-browser](https://github.com/pestphp/pest-plugin-browser) v4 |
| Environment        | [DDEV](https://ddev.com/) (Symfony + Keycloak + MariaDB)     |
| Auth provider      | Keycloak 26 (OAuth2/OpenID Connect)                          |

## Prerequisites

1. DDEV must be running: `ddev start`
2. The Keycloak service must be up (included in the DDEV compose setup)
3. Composer and npm dependencies must be installed. The normal project setup
   installs the Playwright npm package, but deliberately does not download any
   browser executables.
4. The `db` database must have migrations applied and server data populated:

```bash
ddev exec php bin/console doctrine:migrations:migrate --no-interaction
```

## Optional Playwright Browser Components

Browser components are **not installed by default**. This applies to normal
local setup, `ddev start`, and the GitHub Actions artifact build.

There are two separate parts to the browser-test setup:

- The `playwright` npm package contains the Node.js command-line tool and the
  code used by Pest. It is installed with the project's other JavaScript
  dependencies by `npm install`.
- The browser components are the much larger Chromium executables, including
  Chromium Headless Shell, plus operating-system libraries needed to run them.
  These are installed separately and only when requested.

Most developers do not run the end-to-end browser suite on every installation.
Leaving the browser components out keeps the initial setup and DDEV startup
quicker, avoids downloading and storing several hundred megabytes, and keeps CI
jobs smaller. GitHub CI currently builds assets and runs the non-browser test
suites, so it explicitly sets `PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1` and does not
install Chromium.

### Install inside DDEV (recommended)

After an initial installation without browser components, run:

```bash
ddev exec npm run playwright:install
```

`npm run playwright:install` executes the project script defined in
`package.json`. It uses the Playwright version already installed by the project;
you do not need to install npm or Playwright globally. The command installs the
Linux system libraries required by Chromium, full Chromium for headed tests,
and Chromium Headless Shell for the default headless tests.

For a completely new checkout, the relevant sequence is:

```bash
ddev start
ddev exec npm install
ddev exec npm run playwright:install
```

Check which browser components are installed with:

```bash
ddev exec npm run playwright:list
```

Playwright stores the downloaded files in its cache inside the DDEV web
container. Re-run `ddev exec npm run playwright:install` after the web container
is deleted or rebuilt, after updating the Playwright npm package, or whenever a
test reports that its expected browser executable is missing. Installing the
browsers on the host does not install them inside DDEV; the two environments
have separate caches.

### Install on the host instead

Only use this option when running Pest directly on the host rather than with
`ddev exec`:

```bash
npm install
npm run playwright:install
```

The second command may request administrator privileges on Linux because it
also installs Chromium's operating-system packages. DDEV is recommended because
it keeps these packages inside the development container.

The `PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD` setting prevents implicit downloads
during dependency installation. It does not block the explicit
`playwright:install` command above.

## Running Tests

### All browser tests

```bash
ddev exec vendor/bin/pest testsBrowser/
```

### Specific test file

```bash
ddev exec vendor/bin/pest testsBrowser/DashboardTest.php
```

### Filter by test name

```bash
ddev exec vendor/bin/pest testsBrowser/DashboardTest.php --filter 'dashboard is accessible'
```

### Testdox output (human-readable test names)

```bash
ddev exec vendor/bin/pest testsBrowser/DashboardTest.php --testdox
```

## Headed Mode (Visible Browser Window)

By default, Playwright runs headless. To open a visible browser window for debugging,
set the `PLAYWRIGHT_HEADED` environment variable:

### In DDEV

Add to `.ddev/.env.web`:

```
PLAYWRIGHT_HEADED=true
```

Then restart DDEV:

```bash
ddev restart
```

For headed mode to work, the DDEV web container must have access to your host's
X11 display. Add a Docker Compose override at `.ddev/docker-compose.display.yaml`:

```yaml
services:
  web:
    environment:
      - DISPLAY=$DISPLAY
    volumes:
      - /tmp/.X11-unix:/tmp/.X11-unix
```

Then allow X11 connections from the container (run on host):

```bash
xhost +local:
```

### Without DDEV (standalone PHP local server)

```bash
PLAYWRIGHT_HEADED=true vendor/bin/pest testsBrowser/
```

### Running from the Host Machine

Browser tests can be run directly on the host machine without going through the
DDEV container. This is especially useful for headed mode, where the host
machine's native display manager handles the browser window directly — no X11
forwarding or virtual framebuffer needed.

**Prerequisites (host):**

- PHP 8.3+ with the `sockets` extension
- Composer dependencies installed: `composer install`
- Node.js dependencies, including the Playwright npm package: `npm install`
- Playwright browser components installed on demand: `npm run playwright:install`
- DDEV must be running (`ddev start`) — tests make real HTTP requests to
  `https://jitsi-admin.ddev.site`

**Running tests on the host:**

```bash
vendor/bin/pest testsBrowser/DashboardTest.php
```

**Headed mode on the host:**

```bash
PLAYWRIGHT_HEADED=true vendor/bin/pest testsBrowser/DashboardTest.php
```

To enable headed mode by default on the host (no need to prefix every command),
add it to `.env.local`:

```
PLAYWRIGHT_HEADED=true
```

The Symfony Dotenv component (loaded by `tests/bootstrap.php`) reads `.env.local`
on every test run, pushing the variable into both `$_ENV` and `getenv()`. The
`tests/Pest.php` configuration picks it up from either source.

Set to `false` or remove the line to switch back to headless mode.

On the host, headed mode works natively because the host's display manager
handles the browser window directly.

| Platform           | Headed Mode Support                                         |
|--------------------|-------------------------------------------------------------|
| **Linux**          | Works natively with X11/Wayland display                     |
| **macOS**          | Works natively — Chromium opens as a regular macOS app      |
| **Windows (WSL2)** | Requires an X server on Windows (e.g. VcXsrv) and `DISPLAY` |
|                    | set in WSL to point at it                                   |

**Headed mode on WSL2:**

1. Install an X server on Windows (e.g. [VcXsrv](https://sourceforge.net/projects/vcxsrv/))
2. Start VcXsrv with "Disable access control" enabled
3. In WSL2, set the `DISPLAY` variable:

```bash
export DISPLAY=$(ip route show default | cut -d' ' -f3):0
```

4. Run the tests:

```bash
PLAYWRIGHT_HEADED=true vendor/bin/pest testsBrowser/
```

## Test Structure

```
tests/
├── Pest.php              # Pest configuration (Playwright init, hooks)
├── BrowserAuth.php       # Auth helper trait: Keycloak OIDC login flow
├── TestCase.php          # Base test case (Symfony WebTestCase)

testsBrowser/
├── HomepageTest.php      # Public homepage tests (no auth)
├── AuthenticatedTest.php # Keycloak OIDC flow verification
├── DashboardTest.php     # Authenticated dashboard tests
```

## Writing Authenticated Tests

Import the `BrowserAuth` trait and call `$this->login($page)`:

```php
<?php

declare(strict_types=1);

uses(App\Tests\BrowserAuth::class);

test('your feature', function () {
    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);
    $page = $this->login($page);

    $page->assertPresent('#some-element');
});
```

The `BrowserAuth` trait provides:

| Method      | Description                                          |
|-------------|------------------------------------------------------|
| `login($p)` | Full OIDC flow: click Login → fill Keycloak → submit |
|             | Returns the page on the dashboard                    |

Credentials default to the DDEV Keycloak test user (`jitsi@admin.de` / `jitsiadmin`).
Override by setting `$keycloakUser` and `$keycloakPassword` in the consuming test:

```php
test('login as different user', function () {
    self::$keycloakUser = 'other@admin.de';
    self::$keycloakPassword = 'otherpass';

    $page = $this->visit('https://jitsi-admin.ddev.site/', [
        'ignoreHTTPSErrors' => true,
    ]);
    $page = $this->login($page);
});
```

## Authentication Flow

Each test performs the real OAuth2/OpenID Connect flow:

```
GET    https://jitsi-admin.ddev.site/         # Homepage
CLICK  .hp-nav-right .btn-primary              # Login button
302    → Keycloak authorize endpoint           # Keycloak login page
TYPE   #username, #password                   # Credentials
CLICK  #kc-login                              # Submit
302    → /room/dashboard                       # Authenticated
```

This tests the actual Keycloak integration end-to-end — no mocks, no session injection.

## Configuration

| Env Variable           | Default  | Description                         | Location                    |
|------------------------|----------|-------------------------------------|-----------------------------|
| `PLAYWRIGHT_HEADED`    | (unset)  | Set to `true` for visible browser   | `.env.local` (host),        |
|                        |          |                                     | `.ddev/.env.web` (container) |
| `PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD` | `1` in DDEV and CI | Prevent browser downloads during dependency installation | `.ddev/config.yaml`, GitHub Actions |

## Debugging

### Screenshots

Failed tests automatically save screenshots to `tests/Browser/Screenshots/`.
The path is printed in the failure output:

```
A screenshot of the page has been saved to [Tests/Browser/Screenshots/test_name].
```

### Playwright Traces

To enable trace recording, add to your test:

```php
use Pest\Browser\Playwright\Playwright;

Playwright::trace();
```

### Common Issues

**"Playwright is outdated" error:**
Reinstall matching browser binaries:

```bash
ddev exec npm run playwright:install
```

This can occur after updating the Playwright npm package or rebuilding the DDEV
web container because each Playwright release expects specific browser builds.

**Browser executable is missing:**
The optional browser components have not been installed in the environment
where Pest is running. For DDEV, run:

```bash
ddev exec npm run playwright:install
```

For tests run directly on the host, run `npm run playwright:install` on the
host instead.

**500 errors on page load:**
Ensure the dev database has the schema and server data:

```bash
ddev exec php bin/console doctrine:migrations:migrate --no-interaction
```

**Test hangs before running:**
The Playwright server process may not start. Verify Playwright works standalone:

```bash
ddev exec npx playwright --version
```

**`localhost` URLs redirect incorrectly:**
Always use `https://jitsi-admin.ddev.site` as the base URL. The Keycloak OAuth2
redirect URIs are configured for this hostname.
