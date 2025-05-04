<?php

namespace InertiaBundle\Document\Areabrick;

use InertiaBundle\Service\Inertia;
use Pimcore\Extension\Document\Areabrick\AbstractAreabrick as BaseAreabrick;
use Pimcore\Model\Document\Editable\Area\Info;
use Pimcore\Model\Document\Editable\Areablock;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractInertiaAreabrick extends BaseAreabrick
{
    protected Inertia $inertia;

    public function __construct(Inertia $inertia)
    {
        $this->inertia = $inertia;
    }

    public function getTemplateLocation(): string
    {
        return static::TEMPLATE_LOCATION_GLOBAL;
    }

    public function getTemplateSuffix(): string
    {
        return static::TEMPLATE_SUFFIX_TWIG;
    }

    public function getTemplate(): ?string
    {
        return null;
    }

    public function action(Info $info): ?Response
    {
        $info->setParam('areabrick', $this);
        return null;
    }

    public function getInertiaProps(Info $info): array
    {
        $props = [];

        $areablock = $info->getEditable();
        $editables = $info->getDocument()->getEditables();
        $indiceIndex = $info->getIndex();

        if (!($areablock instanceof Areablock) || !count($editables)) {
            return [];
        }

        $indices = $areablock->getData();

        if (count($indices) <= ($indiceIndex ?? -1) || $indices[$indiceIndex]['type'] !== $info->getId()) {
            return [];
        }

        $blockName = $areablock->getName();
        $currentKey = $indices[$indiceIndex]['key'];

        foreach ($editables as $editable) {
            if ($editable instanceof Areablock) {
                continue;
            }

            $editableName = $editable->getName();
            $editablePrefix = vsprintf('%s:%s', [$blockName, $currentKey]);

            if (strpos($editableName, $editablePrefix) === 0) {
                $key = str_replace($editablePrefix . '.', '', $editableName);
                $props[$key] = $editable;
            }
        }

        return $props;
    }

    abstract public function getInertiaComponent(): string;

}
