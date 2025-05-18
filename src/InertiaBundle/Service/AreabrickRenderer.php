<?php declare(strict_types=1);

namespace InertiaBundle\Service;

use Pimcore\Model\Document\Editable;
use Pimcore\Model\Document\Editable\Areablock;
use Pimcore\Http\Request\Resolver\DocumentResolver;

readonly class AreabrickRenderer
{

    public function __construct(
        private DocumentResolver $documentResolver
    )
    {
    }

    public function getAreablockData(string $name): array
    {
        $document = $this->documentResolver->getDocument();

        if (!$document) {
            return [];
        }

        $editables = $document->getEditables();

        if (!($areablock = $editables[$name] ?? null)) {
            return [];
        }

        $data = [];
        foreach ($areablock->getData() as $index => $brick) {

            if ($brick['hidden']) {
                continue;
            }

            $data[] = [
                'type' => $brick['type'],
                'index' => $index,
                'key' => $brick['key'],
                'data' => $this->getBrickData($editables, $name, $brick['key'])
            ];
        }

        return $data;
    }

    private function getBrickData(array $editables, string $blockName, string $key): array
    {
        $data = [];

        foreach ($editables as $editable) {
            if ($editable instanceof Areablock) {
                continue;
            }

            $editableName = $editable->getName();
            $editablePrefix = vsprintf('%s:%s', [$blockName, $key]);

            if (strpos($editableName, $editablePrefix) != 0) {
                continue;
            }

            $name = str_replace($editablePrefix . '.', '', $editableName);
            $data[$name] = $this->convertToSerializable($editable);
        }

        return $data;
    }

    protected function convertToSerializable(Editable $editable): mixed
    {
        return match (true) {
            $editable instanceof Editable\Input => $editable->getData(),
            $editable instanceof Editable\Wysiwyg => $editable->getText(),
            default => $editable->getData(),
        };
    }

}
