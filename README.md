[deutsch](README_de.md)

<div align="center">
  <img src="docs/images/header.webp" alt="Jitsi Admin Header" width="100%" />
</div>

<br/>

<h1 align="center">Jitsi Admin</h1>

<div align="center">
  <a href="code_of_conduct.md">
    <img src="https://img.shields.io/badge/Contributor%20Covenant-v2.0%20adopted-ff69b4.svg" />
  </a>
  <a href="https://crowdin.com/project/jitsi-admin">
    <img src="https://badges.crowdin.net/jitsi-admin/localized.svg" />
  </a>
  <a href="https://github.com/H2-invent/jitsi-admin/actions/workflows/pipeline-test.yml">
    <img src="https://github.com/H2-invent/jitsi-admin/actions/workflows/pipeline-test.yml/badge.svg" />
  </a>
</div>

<hr/>

<h2>Overview</h2>

<p>
<strong>Jitsi Admin</strong> (formerly Jitsi Manager) is a central administration platform
for operating <strong>Jitsi</strong> and <strong>Livekit</strong> based video conferencing infrastructures.
</p>

<p>
The platform focuses on <strong>control, security and scalability</strong>.
It is built for organizations that want predictable behavior instead of ad-hoc meetings
and unmanaged conference links.
</p>

<hr/>

<h2>Architecture</h2>

<ul>
  <li><strong>Frontend:</strong> Purpose-built UI, optimized for daily administrative workflows</li>
  <li><strong>Backend:</strong> Conference lifecycle management, scheduling, JWT handling</li>
  <li><strong>Media Layer:</strong>
    <ul>
      <li>Jitsi (classic deployments)</li>
      <li><strong>Livekit</strong> (recommended for performance and stability)</li>
    </ul>
  </li>
</ul>

<p>
Authentication can be operated with or without JWTs.
Running public conferences without protection will result in abuse. That is not a bug.
</p>

<hr/>

<h2>Key Features</h2>

<table>
  <tr>
    <td><strong>Media</strong></td>
    <td>
      Livekit integration · Jitsi support · Low latency WebRTC · Direct calls · Webinars
    </td>
  </tr>
  <tr>
    <td><strong>Scheduling</strong></td>
    <td>
      Series appointments · Polls · Outlook / iCal · Calendly integration
    </td>
  </tr>
  <tr>
    <td><strong>Identity</strong></td>
    <td>
      LDAP · SSO (Keycloak etc.) · Guest access via JWT
    </td>
  </tr>
  <tr>
    <td><strong>Tooling</strong></td>
    <td>
      Chrome Extension:
      <a href="https://chromewebstore.google.com/detail/meetling-sofortkonferenz/eigjajmppcgpcghajhmbddidmdfeepce">
        Meetling
      </a>
    </td>
  </tr>
</table>

<hr/>

<h2>Installation</h2>

<p>
Jitsi Admin requires shell access and basic Docker or Kubernetes knowledge.
This is infrastructure software, not a hosted SaaS click-install.
</p>

<h3>Docker</h3>

<p>
Recommended for small to medium installations.
</p>

<p>
👉 <a href="installDocker.md">Docker installation instructions</a>
</p>

<h3>Kubernetes / Helm</h3>

<p>
Recommended for production and high availability setups.
</p>

<p>
👉 <a href="https://reg.h2-invent.com/harbor/projects/16/repositories/meetling/artifacts-tab">
Helm Chart Repository
</a>
</p>

<hr/>

<h2>Livekit Evaluation</h2>

<ol>
  <li>Install Livekit</li>
  <li>Configure it via the Jitsi Admin UI</li>
  <li>Run a conference</li>
</ol>

<p>
The difference in latency and media quality compared to classic Jitsi is obvious.
</p>

<hr/>

<h2>User Interface</h2>

<h3>Dashboard</h3>
<p>Central overview of conferences and system state.</p>
<img src="docs/images/dashboard-heading.png" width="100%" />

<h3>Server Management</h3>
<p>Multiple Jitsi servers combined into one logical setup.</p>
<img src="docs/images/server.png" width="100%" />

<h3>Authentication</h3>
<p>SSO based login via Keycloak or compatible providers.</p>
<img src="docs/images/login.png" width="100%" />

<h3>Conference Join</h3>

<p>
<strong>Guests:</strong> Join via email link, JWT is generated automatically.
</p>
<img src="docs/images/join.png" width="100%" />

<p>
<strong>Users:</strong> Join directly via web UI or Electron app.
</p>
<img src="docs/images/joint-internal.png" width="100%" />

<hr/>

<h2>Getting Started</h2>

<ul>
  <li><a href="https://github.com/H2-invent/jitsi-admin/wiki/Get-Started-English">Getting Started</a></li>
  <li><a href="https://github.com/H2-invent/jitsi-admin/wiki/Minimum-server-requirements-English">Minimum Requirements</a></li>
  <li><a href="https://github.com/H2-invent/jitsi-admin/wiki/API-Endpoints">API Documentation (German)</a></li>
</ul>

<p>
Project website: <a href="https://jitsi-admin.de">https://jitsi-admin.de</a>
</p>

<hr/>

<h2>Community</h2>

<p>
Matrix channel:<br/>
<strong>#jitsi-admin:h2-invent.com</strong><br/>
<a href="https://matrix.to/#/#jitsi-admin:h2-invent.com">Join via matrix.to</a>
</p>

<p>
Community call every even Thursday at 18:00 (CEST).
</p>

<p>
<a href="http://jitsi-admin.de/subscribe/self/4754e33d3ee9a6c40a2bf04ffa1528c7">
Subscribe here
</a>
</p>

<hr/>

<h2>Mailing Lists</h2>

<p>
<a href="https://lists.h2-invent.com/forms/nfrm_weLJnLY5">Join mailing list</a><br/>
Technical updates only. No marketing. Double opt-in.
</p>

<hr/>

<h2>Partners & Sponsors</h2>

<p>
<a href="https://h2-invent.com">
  <img src="docs/images/h2-invent.png" height="60"/>
</a><br/>
Core maintainer
</p>

<p>
<a href="https://meetling.de">
  <img src="docs/images/meetling.png" height="60"/>
</a><br/>
Official SaaS solution
</p>

<p>
<img src="docs/images/readi.png" height="60"/><br/>
Public sector cooperation (Baden-Württemberg)
</p>

<hr/>

<h2>Support Policy</h2>

<p>
Jitsi Admin is free software.
Free individual support is not included.
</p>

<p>
Invalid issues and support requests will be closed.
This is intentional.
</p>

<hr/>

<h2>License</h2>

<p>
AGPL-3.0<br/>
<a href="https://www.gnu.org/licenses/agpl-3.0.en.html">License text</a> ·
<a href="LICENSE">LICENSE file</a>
</p>

<hr/>

<h2>Customization</h2>

<p>
Use <code>.env.custom</code> for overrides.<br/>
After changes run:
</p>

<pre><code>bash installDocker.sh</code></pre>

<p>
<code>docker-compose up</code> is not sufficient.
</p>

<hr/>

<h2>Updates</h2>

<ul>
  <li><a href="update_instruction_0.75.x...0.76.x.md">0.75.x → 0.76.x</a></li>
  <li><a href="update_instruction_0.74.x...0.75.x.md">0.74.x → 0.75.x</a></li>
  <li><a href="update_instruction_0.73.x...0.74.x.md">0.73.x → 0.74.x</a></li>
  <li><a href="update_instruction_0.72.x...0.73.x.md">0.72.x → 0.73.x</a></li>
</ul>
