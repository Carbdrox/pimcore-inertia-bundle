<?php declare(strict_types=1);

namespace InertiaBundle\Service;

use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\ClassDefinition\Data\ImageGallery;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Image as ImageField;

class AssetHelperService
{
    public function extractAssetIds(AbstractObject $object): array
    {
        $assetIds = [];
        $class = $object->getClass();

        if (!$class) {
            return $assetIds;
        }

        $fieldDefinitions = $class->getFieldDefinitions();

        foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
            if ($this->isAssetField($fieldDefinition)) {
                $fieldValue = $object->get($fieldName);
                $this->extractAssetIdsFromField($fieldValue, $assetIds);
            }
        }

        return array_values(array_unique($assetIds));
    }

    private function isAssetField($fieldDefinition): bool
    {
        return $fieldDefinition instanceof ImageField
            || $fieldDefinition instanceof ImageGallery
            || ($fieldDefinition instanceof ManyToManyRelation &&
                $fieldDefinition->getObjectsAllowed() === false &&
                $fieldDefinition->getAssetsAllowed() === true);
    }

    private function extractAssetIdsFromField($fieldValue, array &$assetIds): void
    {
        if (empty($fieldValue)) {
            return;
        }

        if ($fieldValue instanceof Asset) {
            $assetIds[] = $fieldValue->getId();
            return;
        }

        if (is_array($fieldValue) || $fieldValue instanceof \Traversable) {
            foreach ($fieldValue as $item) {
                if ($item instanceof Asset) {
                    $assetIds[] = $item->getId();
                }
            }
        }
    }

    public function prepareObjectForInertia(AbstractObject $object): array
    {
        $data = [];
        $class = $object->getClass();

        if (!$class) {
            return $data;
        }

        $fieldDefinitions = $class->getFieldDefinitions();

        foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
            $fieldValue = $object->get($fieldName);

            if ($this->isAssetField($fieldDefinition)) {
                $data[$fieldName] = $this->convertAssetFieldToIds($fieldValue);
            } else {
                $data[$fieldName] = $fieldValue;
            }
        }

        $data['id'] = $object->getId();
        $data['path'] = $object->getPath();
        $data['published'] = $object->getPublished();
        $data['key'] = $object->getKey();
        $data['className'] = $object->getClassName();

        return $data;
    }

    private function convertAssetFieldToIds($fieldValue)
    {
        if (empty($fieldValue)) {
            return null;
        }

        if ($fieldValue instanceof Asset) {
            return $fieldValue->getId();
        }

        if (is_array($fieldValue) || $fieldValue instanceof \Traversable) {
            $ids = [];
            foreach ($fieldValue as $item) {
                if ($item instanceof Asset) {
                    $ids[] = $item->getId();
                }
            }
            return $ids;
        }

        return $fieldValue;
    }
}
