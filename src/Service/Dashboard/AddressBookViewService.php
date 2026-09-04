<?php

declare(strict_types=1);

namespace App\Service\Dashboard;

use App\Entity\Deputy;
use App\Entity\User;
use App\Repository\DeputyRepository;
use App\Service\FormatName;
use App\Service\ParticipantSearchService;
use App\Service\ServerUserManagment;
use App\Service\Theme\ThemeService;
use App\Service\UserCreatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Builds the structured JSON view-model for the React address book (dashboard).
 *
 * All business rules stay server-side: the React application receives pre-computed
 * capabilities, urls, labels and values and renders them. Favourite/deputy/delete/add
 * mutations are still performed through the existing AJAX endpoints, but the React
 * component updates its own state after a successful response instead of re-fetching
 * a server-rendered fragment.
 */
class AddressBookViewService
{
    /** @var array<int, array<int, Deputy>> */
    private array $deputyCache = [];

    public function __construct(
        private readonly ServerUserManagment $serverUserManagment,
        private readonly UserCreatorService $userCreatorService,
        private readonly ThemeService $themeService,
        private readonly FormatName $formatName,
        private readonly ParticipantSearchService $participantSearchService,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $em,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UploaderHelper $uploaderHelper,
    ) {
    }

    /**
     * Builds the complete initial address book state for one user.
     */
    public function buildState(User $user): array
    {
        $servers = $this->serverUserManagment->getServersFromUser($user);

        $contacts = [];
        foreach ($user->getAddressbook() as $contact) {
            $contacts[] = $this->buildContact($user, $contact, $servers);
        }

        return [
            'contacts' => $contacts,
            'filters' => $this->buildFilters(),
            'config' => [
                'doAllowUserCreation' => $this->userCreatorService->doAllowUserCreation(),
                'trashOnAdressBook' => $this->trashOnAdressBook(),
                'urls' => [
                    'addAjax' => $this->urlGenerator->generate('adressbook_add_user_ajax'),
                ],
                'translations' => $this->buildTranslations(),
            ],
        ];
    }

    /**
     * Serializes a single address book contact for the React view-model. Also used by
     * the add-contact endpoint so a newly added contact can be appended to React state.
     */
    public function buildContact(User $user, User $contact, array $servers): array
    {
        $isFavorite = $user->getAdressbookFavorites()->contains($contact);

        $categories = array_values($contact->getCategories() ?? []);
        $categories[] = 'all';
        if ($isFavorite) {
            $categories[] = 'favorite';
        }
        $categories = array_values(array_unique($categories));

        $nameNoIcon = $this->participantSearchService->buildShowInFrontendStringNoString($contact);
        $hasName = ($contact->getFirstName() !== null && $contact->getFirstName() !== '')
            || ($contact->getLastName() !== null && $contact->getLastName() !== '');
        $showNameFrontend = $this->parameterBag->get('laf_showNameFrontend');
        $name = $hasName ? $this->formatName->formatName((string) $showNameFrontend, $contact) : '';

        $adhoc = [];
        foreach ($servers as $server) {
            $adhoc[] = [
                'serverName' => $server->getServerName(),
                'url' => $this->urlGenerator->generate('add_hoc_confirm', [
                    'serverId' => $server->getId(),
                    'userId' => $contact->getId(),
                ]),
            ];
        }

        return [
            'id' => $contact->getId(),
            'uid' => $contact->getUid(),
            'email' => $contact->getEmail(),
            'username' => $contact->getUsername(),
            'name' => $name,
            'nameNoIcon' => $nameNoIcon,
            'initial' => $nameNoIcon !== '' ? mb_strtoupper(mb_substr($nameNoIcon, 0, 1)) : '#',
            'indexer' => $contact->getIndexer() ?? '',
            'categories' => $categories,
            'isFavorite' => $isFavorite,
            'isDeputy' => $user->getDeputy()->contains($contact),
            'isDeputyFromLdap' => $this->isDeputyFromLdap($user, $contact),
            'canMakeDeputy' => !$this->userIsDisallowedToMakeDeputy($contact),
            'canDelete' => $this->trashOnAdressBook(),
            'profilePicture' => $contact->getProfilePicture()
                ? $this->uploaderHelper->asset($contact->getProfilePicture(), 'documentFile')
                : null,
            'color' => $this->colorFromString((string) $contact->getUsername()),
            'avatarText' => mb_strtoupper(mb_substr($nameNoIcon, 0, 2)),
            'favoriteUrl' => $this->urlGenerator->generate('app_adressbook_favorite_ajax', [
                'userId' => $contact->getUid(),
            ]),
            'removeUrl' => $this->urlGenerator->generate('adressbook_remove_user_ajax', [
                'id' => $contact->getId(),
            ]),
            'deputyUrl' => $this->urlGenerator->generate('app_deputy_add_ajax', [
                'deputyUid' => $contact->getUid(),
            ]),
            'adhoc' => $adhoc,
        ];
    }

    /**
     * Serializes one address book contact for the current user. Used by the add-contact
     * AJAX endpoint so a newly added contact can be appended to the React state.
     */
    public function serializeContact(User $user, User $contact): array
    {
        $servers = $this->serverUserManagment->getServersFromUser($user);

        return $this->buildContact($user, $contact, $servers);
    }

    private function buildFilters(): array
    {
        $filters = [];

        $config = $this->themeService->getApplicationProperties('LAF_ADDRESSBOOK_CHECKBOX_LABEL_2_VALUE');
        if (is_array($config) && count($config) > 0) {
            $index = 1;
            foreach ($config as $key => $filter) {
                $filters[] = [
                    'id' => 'addressbookFilter' . $index,
                    'label' => (string) $key,
                    'value' => $filter,
                ];
                $index++;
            }
        }

        $filters[] = [
            'id' => 'addressbookFilterOnline',
            'label' => $this->translator->trans('status.online'),
            'value' => ['online', 'away', 'inMeeting'],
        ];
        $filters[] = [
            'id' => 'addressbookFilterFavorit',
            'label' => $this->translator->trans('favorite.sidebar.title'),
            'value' => ['favorite'],
        ];

        return $filters;
    }

    private function buildTranslations(): array
    {
        $t = $this->translator;

        return [
            'favoritesTitle' => $t->trans('favorite.sidebar.title'),
            'favoritesHelp' => $t->trans('addressbook.favorite.help'),
            'search' => $t->trans('Suche'),
            'filter' => $t->trans('Filter'),
            'newContact' => $t->trans('Neuer Kontakt'),
            'save' => $t->trans('label.speichern', [], 'form'),
            'email' => $t->trans('E-Mail-Adresse'),
            'delete' => $t->trans('Löschen'),
            'confirmDelete' => $t->trans('confirm.delete.adressbookUser'),
            'confirmTitle' => $t->trans('Bestätigung'),
            'confirmOk' => $t->trans('OK'),
            'confirmCancel' => $t->trans('Abbrechen'),
            'errorTitle' => $t->trans('Fehler'),
            'errorDefault' => $t->trans('Fehler'),
            'deputyAdd' => $t->trans('deputy.add'),
            'deputyRemove' => $t->trans('deputy.remove'),
            'deputyHelp' => $t->trans('deputy.help'),
            'deputyLdapDisabled' => $t->trans('deputy.fromLdap.disabled'),
            'deputyHelpLdap' => $t->trans('deputy.help.ldap'),
            'deputyTooltip' => $t->trans('deputy.text.isDeputy'),
            'adhocText' => $t->trans('Wollen Sie mit diesem Teilnehmer eine Konferenz starten'),
            'statusOnline' => $t->trans('status.online'),
        ];
    }

    private function isDeputyFromLdap(User $manager, User $deputy): bool
    {
        if (!isset($this->deputyCache[$manager->getId()])) {
            /** @var DeputyRepository $repo */
            $repo = $this->em->getRepository(Deputy::class);
            $this->deputyCache[$manager->getId()] = $repo->findForManager($manager);
        }

        $dep = $this->deputyCache[$manager->getId()][$deputy->getId()] ?? null;

        return $dep !== null && $dep->isIsFromLdap() === true;
    }

    private function userIsDisallowedToMakeDeputy(User $user): bool
    {
        if (!$user->getLdapUserProperties()) {
            return false;
        }

        return in_array(
            $user->getLdapUserProperties()->getLdapNumber(),
            json_decode((string) $this->parameterBag->get('LDAP_DISALLOW_PROMOTE_DEPUTY'))
        );
    }

    private function trashOnAdressBook(): bool
    {
        $theme = $this->themeService->getTheme();

        return $theme === false || ($theme['trashOnAdressBook'] ?? false) === true;
    }

    private function colorFromString(string $string): string
    {
        $code = dechex(crc32($string));

        return substr($code, 0, 6);
    }
}
