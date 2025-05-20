<?php declare(strict_types=1);

namespace InertiaBundle\Service;

use Pimcore\Translation\Translator;
use Pimcore\Localization\LocaleServiceInterface;
use Symfony\Component\Translation\MessageCatalogueInterface;

class TranslationService
{
    private Translator $translator;
    private LocaleServiceInterface $localeService;

    public function __construct(
        Translator $translator,
        LocaleServiceInterface $localeService
    ) {
        $this->translator = $translator;
        $this->localeService = $localeService;
    }

    public function getAllTranslations(string $domain = 'messages', string $locale = null): array
    {
        if ($locale === null) {
            $locale = $this->localeService->getLocale();
        }

        //@TODO: why do we need to use trans, to recieve messages domain
        $dirtyFix = $this->translator->trans('inertia');
        $catalogue = $this->translator->getCatalogue($locale);


        if (!in_array($domain, $catalogue->getDomains(), true)) {
            return [];
        }

        return $this->extractTranslations($catalogue, $domain);
    }

    public function getTranslationsForDomains(array $domains, string $locale = null): array
    {
        $result = [];

        foreach ($domains as $domain) {
            $result[$domain] = $this->getAllTranslations($domain, $locale);
        }

        return $result;
    }

    private function extractTranslations(MessageCatalogueInterface $catalogue, string $domain): array
    {
        $translations = [];

        $allMessages = $catalogue->all($domain);

        foreach ($allMessages as $key => $translation) {
            $translations[$key] = $translation;
        }

        $fallbackCatalogue = $catalogue->getFallbackCatalogue();
        if ($fallbackCatalogue !== null) {
            $fallbackTranslations = $this->extractTranslations($fallbackCatalogue, $domain);

            foreach ($fallbackTranslations as $key => $translation) {
                if (!isset($translations[$key])) {
                    $translations[$key] = $translation;
                }
            }
        }

        return $translations;
    }
}
