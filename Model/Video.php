<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\VeniaCatalogSampleData\Model;

use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Catalog\Model\ResourceModel\Product\Gallery as GalleryResource;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Api\Data\ProductInterface;
/**
 * Setup sample attributes
 *
 * Class Attribute
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Video
{
    /**
     * @var \Magento\Framework\Setup\SampleData\FixtureManager
     */
    protected $fixtureManager;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var \Magento\Framework\EntityManager\MetadataPool
     */
    private $metadataPool;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $productCollection;

    /**
     * @param SampleDataContext $sampleDataContext
     * @param GalleryResource $galleryResource
     * @param \Magento\ProductVideo\Model\ResourceModel\Video $videoResourceModel
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     */
    public function __construct(
        SampleDataContext $sampleDataContext,
        GalleryResource $galleryResource,
        \Magento\ProductVideo\Model\ResourceModel\Video $videoResourceModel,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory

    ) {
        $this->fixtureManager = $sampleDataContext->getFixtureManager();
        $this->csvReader = $sampleDataContext->getCsvReader();
        $this->galleryResource = $galleryResource;
        $this->videoResourceModel = $videoResourceModel;
        $this->eavConfig = $eavConfig;
        $this->productCollection = $productCollectionFactory->create()->addAttributeToSelect('sku');
    }

    /**
     * @param array $fixtures
     * @throws \Exception
     */
    public function install(array $fixtures)
    {
        foreach ($fixtures as $fileName) {
            $fileName = $this->fixtureManager->getFixture($fileName);
            if (!file_exists($fileName)) {
                continue;
            }

            $rows = $this->csvReader->getData($fileName);
            $header = array_shift($rows);

            foreach ($rows as $row) {
                $data = [];
                foreach ($row as $key => $value) {
                    $data[$header[$key]] = $value;
                }
                $row = $data;
                $productId = $this->getProductIdBySku($row['sku']);
                $linkField = $this->getMetadataPool()->getMetadata(ProductInterface::class)->getLinkField();
                $mediaAttribute = $this->eavConfig->getAttribute('catalog_product', 'media_gallery');

                $id = $this->galleryResource->insertGallery([
                    'attribute_id' => $mediaAttribute->getAttributeId(),
                    "media_type" => \Magento\ProductVideo\Model\Product\Attribute\Media\ExternalVideoEntryConverter::MEDIA_TYPE_CODE,
                    "value" => $row['image']

                ]);

                $this->galleryResource->insertGalleryValueInStore([
                    'value_id' => $id,
                    'store_id' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    $linkField => $productId,
                    'label' => 'Video',
                    'position' => 4
                ]);
                $this->videoResourceModel->insertOnDuplicate([
                    "value_id" => $id,
                    "store_id" => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    "url" => $row['video'],
                    "title" => $row['name']
                ]);
                $this->galleryResource->bindValueToEntity($id, $productId);
            }
        }

    }

    /**
     * @deprecated
     *
     * @return \Magento\Framework\EntityManager\MetadataPool|mixed
     */
    private function getMetadataPool()
    {
        if (!($this->metadataPool)) {
            return ObjectManager::getInstance()->get(
                '\Magento\Framework\EntityManager\MetadataPool'
            );
        } else {
            return $this->metadataPool;
        }
    }

    /**
     * Retrieve product ID by sku
     *
     * @param string $sku
     * @return int|null
     */
    protected function getProductIdBySku($sku)
    {
        if (empty($this->productIds)) {
            foreach ($this->productCollection as $product) {
                $this->productIds[$product->getSku()] = $product->getId();
            }
        }
        if (isset($this->productIds[$sku])) {
            return $this->productIds[$sku];
        }
        return null;
    }

}