<?php declare(strict_types=1);

namespace InertiaBundle\Service;

use Pimcore\Model\Document\Editable;
use Pimcore\Model\Element\ElementInterface;
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
            $editable instanceof Editable\Input,
            $editable instanceof Editable\Textarea,
            $editable instanceof Editable\Wysiwyg,
            $editable instanceof Editable\Numeric,
            $editable instanceof Editable\Checkbox,
            $editable instanceof Editable\Date,
            $editable instanceof Editable\Select,
            $editable instanceof Editable\Embed => $editable->getData(),

            $editable instanceof Editable\Multiselect => $editable->getData() ?: [],

            $editable instanceof Editable\Link => $this->serializeLink($editable),

            $editable instanceof Editable\Relation,
            $editable instanceof Editable\Relations => $this->serializeRelation($editable),

            $editable instanceof Editable\Image => $this->serializeImage($editable),
            $editable instanceof Editable\Video => $this->serializeVideo($editable),
            $editable instanceof Editable\PDF => $this->serializePdf($editable),

            $editable instanceof Editable\Renderlet => $this->serializeRenderlet($editable),
            $editable instanceof Editable\Snippet => $this->serializeSnippet($editable),
            $editable instanceof Editable\Block => $this->serializeBlock($editable),

            default => $editable->getData(),
        };
    }

    private function serializeLink(Editable\Link $editable): ?array
    {
        if ($editable->isEmpty()) {
            return null;
        }

        return [
            'title' => $editable->getTitle(),
            'text' => $editable->getText(),
            'target' => $editable->getTarget(),
            'href' => $editable->getHref(),
            'path' => $editable->getData()['path'] ?? null,
            'parameters' => $editable->getParameters(),
            'anchor' => $editable->getAnchor(),
            'class' => $editable->getClass(),
            'relation' => $editable->getRel(),
            'tab_index' => $editable->getTabindex(),
            'accesskey' => $editable->getAccesskey(),
        ];
    }

    private function serializeRelation(Editable\Relation | Editable\Relations $editable): ?array
    {
        if ($editable instanceof Editable\Relations) {
            return array_map(fn($element) => $this->serializeRelationElement($element), $editable->getElements());
        }

        if (!$element = $editable->getElement()) {
            return null;
        }

        return $this->serializeRelationElement($element);
    }

    private function serializeRelationElement(ElementInterface $element): array
    {
        //@TODO: make hookable to support custom logics for different objects
        return [
            'id' => $element->getId(),
            'type' => $element->getType(),
            'published' => method_exists($element, 'isPublished') ? $element->isPublished() : true,
        ];
    }

    private function serializeImage(Editable\Image $editable): ?array
    {
        if ($editable->isEmpty() || !$image = $editable->getImage()) {
            return null;
        }

        //@TODO: maybe add thumbnailing here?
        return [
            'id' => $image->getId(),
            'alt' => $editable->getAlt(),
            'thumbnail' => $editable->getThumbnailConfig(),
            'src' => $editable->getSrc(),
            'fullpath' => $image->getFullPath(),
        ];
    }

    private function serializeVideo(Editable\Video $editable): ?array
    {
        if ($video = $editable->getVideoAsset()) {
            return [
                'id' => $video->getId(),
                'type' => 'asset',
                'title' => $editable->getData()['title'] ?? null,
                'description' => $editable->getData()['description'] ?? null,
                'fullpath' => $video->getFullPath(),
                'poster' => $editable->getPosterAsset() ? $editable->getPosterAsset()->getFullPath() : null,
            ];
        }

        if ($editable->getVideoType() === 'youtube') {
            return [
                'type' => $editable->getVideoType(),
                'id' => $editable->getData()['id'] ?? null,
                'title' => $editable->getData()['title'] ?? null,
                'description' => $editable->getData()['description'] ?? null,
                'poster' => $editable->getPosterAsset() ? $editable->getPosterAsset()->getFullPath() : null,
            ];
        }

        return null;
    }

    private function serializePdf(Editable\PDF $editable): ?array
    {
        if ($editable->isEmpty() || !$pdf = $editable?->getElement()) {
            return null;
        }

        return [
            'id' => $pdf->getId(),
            'fullpath' => $pdf->getFullPath(),
        ];
    }

    private function serializeRenderlet(Editable\Renderlet $editable): ?array
    {
        $data = $editable->getData();
        $element = $editable->getElement();

        if (!$element) {
            return $data;
        }

        //@TODO: supply component to render + check if renderlet can be inertia component
        return array_merge($data, [
            'id' => $element->getId(),
            'type' => $element->getType(),
            'data' => $editable->getData(),
            'published' => method_exists($element, 'isPublished') ? $element->isPublished() : true,
        ]);
    }

    private function serializeSnippet(Editable\Snippet $editable): ?array
    {
        $snippet = $editable->getElement();
        if (!$snippet) {
            return null;
        }

        //@TODO: supply component to render + check if snippet can be inertia component
        return [
            'id' => $snippet->getId(),
            'path' => $snippet->getFullPath(),
            'controller' => $snippet->getController(),
            'action' => $snippet->getAction(),
        ];
    }

    private function serializeBlock(Editable\Block $editable): array
    {
        $data = [];
        $indices = $editable->getIndices();

        foreach ($indices as $index) {
            $blockData = [];
            foreach ($editable->getElements() as $name => $child) {
                $realName = $editable->getName() . ':' . $index . '.' . $name;
                $blockData[$name] = $this->convertToSerializable($child->getEditable($realName));
            }
            $data[] = $blockData;
        }

        return $data;
    }

}
