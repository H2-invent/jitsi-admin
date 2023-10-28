<?php

namespace App\Service;

use App\Entity\Rooms;
use Doctrine\ORM\EntityManagerInterface;
use H2Entwicklung\Signature\CheckSignature;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ThemeService
{
    private $parameterBag;
    private $logger;
    private RequestStack $request;
    private CheckSignature $checkSignature;
    private CacheItemPoolInterface $cache;

    public function __construct(
        CacheItemPoolInterface      $filesystemAdapter,
        CheckSignature              $checkSignature,
        RequestStack                $request,
        HttpClientInterface         $httpClient,
        ParameterBagInterface       $parameterBag,
        LoggerInterface             $logger,
        private TranslatorInterface $translator)
    {
        $this->parameterBag = $parameterBag;
        $this->client = $httpClient;
        $this->logger = $logger;
        $this->request = $request;
        $this->checkSignature = $checkSignature;
        $this->cache = $filesystemAdapter;
    }

    public function getTheme(?Rooms $room = null)
    {
        if ($room) {
            if ($room->getHostUrl()) {
                $url = str_replace('https://', '', $room->getHostUrl());
                $url = str_replace('http://', '', $url);
            } else {
                return false;
            }
        } else {
            if ($this->request && $this->request->getCurrentRequest()) {
                $url = $this->request->getCurrentRequest()->getHost();
            } else {
                return false;
            }
        }


        try {
            $value = $this->cache->get(
                'theme_' . $url,
                function (ItemInterface $item) use ($url) {
                    $item->expiresAfter(3600);

                    $finder = new Finder();
                    $finder->files()->in($this->parameterBag->get('kernel.project_dir') . '/theme/')->name($url . '.' . 'theme.json.signed');
                    if ($finder->count() > 0) {
                        $arr = iterator_to_array($finder);
                        $theme = reset($arr)->getContents();

                        $valid = $this->checkSignature->verifySignature($theme);
                        if ($valid) {
                            $res = $this->checkSignature->verifyValidUntil($theme);
                            if ($res !== false) {
                                return $res;
                            }
                            $this->logger->error('Theme valid until is before now');
                        } else {
                            $this->logger->error('Signature invalid');
                        }
                    }
                    return false;
                }
            );
            return $value;
        } catch (\Exception $exception) {
        }
        return false;
    }

    public function getThemeProperty($property)
    {
        $theme = $this->getTheme();
        if ($theme) {
            try {
                return $theme[$property];
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    public function getApplicationProperties($input)
    {

        $variable = null;
        if ($this->parameterBag->has($input)) {
            $variable = $this->parameterBag->get($input);
        }

        $tmp = $this->getThemeProperty($input);

        if ($tmp !== null) {
            try {
                $res = json_decode($tmp, true);
                if ($res=== null) {
                    return $tmp;
                }
                if ($res === false){
                    return $res;
                }
                return $res;
            } catch (\Exception $exception) {

                return $tmp;
            }
        }

        try {
            $res = null;
            if ($variable) {
                $res = json_decode($variable, true);
            }

            if ($res=== null) {
                return $variable;
            }
            if ($res === false){
                return $res;
            }
            return $res;
        } catch (\Exception $exception) {
            return $variable;
        }
    }

    public function checkRemainingDays(): ?int
    {
        $validUntil = $this->getThemeProperty('validUntil');
        if ($validUntil) {
            $validDate = new \DateTime($validUntil);
            $now = new \DateTime();
            $daysDifff = intval(($now->diff($validDate))->format('%R%a'));
            if ($daysDifff < $this->getApplicationProperties('SECURITY_THEME_REMINDER_DAYS')) {
                $this->request->getSession()->getBag('flashes')->add(
                    $daysDifff > 0 ? 'warning' : 'danger',
                    $this->translator->trans('theme.invalid.', array('{days}' => $daysDifff))
                );
            }
            return $daysDifff;
        }
        return null;
    }

    public function showAllThemes(): bool|array
    {
        $finder = new Finder();
        $finder->files()->in($this->parameterBag->get('kernel.project_dir') . '/theme/')->name('*.theme.json.signed');
        if (!$finder->hasResults()) {
            return false;
        }

        $res = [];
        $arr = iterator_to_array($finder);

        foreach ($arr as $file) {

            $theme = $file->getContents();

            $tmp = [
                $file->getFilename(),
            ];
            try {
                $tmp[] = json_decode($theme, true)['entry']['validUntil'];
            } catch (\Exception $exception) {

            }
            $res[] = $tmp;
        }
        return $res;
    }
}
