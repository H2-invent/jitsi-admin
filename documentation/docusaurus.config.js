// @ts-check
// Note: type annotations allow type checking and IDEs autocompletion

const lightCodeTheme = require('prism-react-renderer/themes/github');
const darkCodeTheme = require('prism-react-renderer/themes/dracula');

/** @type {import('@docusaxurus/types').Config} */
const config = {
  title: 'Jitsi-Admin',
  tagline: 'Your effienzy tool based on Open-Source',
  url: 'https://meetling.de',
  baseUrl: '/',
  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'warn',
  favicon: 'img/favicon.ico',

  // GitHub pages deployment config.
  // If you aren't using GitHub pages, you don't need these.
  organizationName: 'h2-invent', // Usually your GitHub org/user name.
  projectName: 'jitsi-admin', // Usually your repo name.

  // Even if you don't use internalization, you can use this field to set useful
  // metadata like html lang. For example, if your site is Chinese, you may want
  // to replace "en" with "zh-Hans".
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          sidebarPath: require.resolve('./sidebars.js'),
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl:
            'https://github.com/facebook/docusaurus/tree/main/packages/create-docusaurus/templates/shared/',
        },
        blog: {
          showReadingTime: true,
          // Please change this to your repo.
          // Remove this to remove the "edit this page" links.
          editUrl:
            'https://github.com/facebook/docusaurus/tree/main/packages/create-docusaurus/templates/shared/',
        },
        theme: {
          customCss: require.resolve('./src/css/custom.css'),
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      navbar: {
        title: 'Jitsi-Admin',
        logo: {
          alt: 'Jitsi-Admin Logo',
          src: 'img/logo_small.png',
        },
        items: [
          {
            type: 'doc',
            docId: 'intro',
            position: 'left',
            label: 'Documentation',
          },
          {to: '/blog', label: 'Blog', position: 'left'},
          {
            href: 'https://github.com/h2-invent/jitsi-admin',
            label: 'GitHub',
            position: 'right',
          },
        ],
      },
      footer: {
        style: 'dark',
        links: [
          {
            title: 'Docs',
            items: [
              {
                label: 'Tutorial',
                to: '/docs/intro',
              },
            ],
          },
          {
            title: 'Community',
            items: [
              {
                label: 'Jitsi forum',
                href: 'https://community.jitsi.org',
              },
              {
                label: 'Github discussions',
                href: 'https://community.jitsi.org',
              },
              {
                label: 'Twitter',
                href: 'https://twitter.com/holzmann_e',
              },
            ],
          },
          {
            title: 'More',
            items: [

              {
                label: 'GitHub',
                href: 'https://github.com/h2-invent/jitsi-admin',
              },
              {
                label: 'H2 Invent GmbH',
                href: 'https://h2-invent.com',
              },
              {
                label: 'Meetling',
                href: 'https://meetling.de',
              },
            ],
          },
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Jitsi-Admin, H2 invent GmbH`,
      },
      prism: {
        theme: lightCodeTheme,
        darkTheme: darkCodeTheme,
      },
    }),
};

module.exports = config;
