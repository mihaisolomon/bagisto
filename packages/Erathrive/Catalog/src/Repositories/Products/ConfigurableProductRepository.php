<?php

declare(strict_types=1);

namespace Erathrive\Catalog\Repositories\Products;

use Erathrive\Catalog\Repositories\BulkProductRepository;
use Illuminate\Container\Container as Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductFlatRepository;
use Webkul\Product\Repositories\ProductRepository;

class ConfigurableProductRepository extends Repository
{
    protected AttributeFamilyRepository $attributeFamilyRepository;

    protected AttributeOptionRepository $attributeOptionRepository;

    protected ProductRepository $productRepository;

    protected ProductFlatRepository $productFlatRepository;

    protected AttributeRepository $attributeRepository;

    protected BulkProductRepository $bulkProductRepository;

    public function __construct(
        Application               $app,
        AttributeFamilyRepository $attributeFamilyRepository,
        AttributeOptionRepository $attributeOptionRepository,
        AttributeRepository       $attributeRepository,
        ProductRepository         $productRepository,
        ProductFlatRepository     $productFlatRepository,
        BulkProductRepository $bulkProductRepository
    ) {
        $this->attributeFamilyRepository = $attributeFamilyRepository;

        $this->attributeOptionRepository = $attributeOptionRepository;

        $this->attributeRepository = $attributeRepository;

        $this->productRepository = $productRepository;

        $this->productFlatRepository = $productFlatRepository;

        $this->bulkProductRepository = $bulkProductRepository;

        parent::__construct($app);
    }

    public function model(): string
    {
        return \Webkul\Product\Contracts\Product::class;
    }

    public function crateProduct(array $mainProduct, $variants): void
    {
        $attributeFamilyData = $this->attributeFamilyRepository->findOneByfield('name', 'Default');

        $productData = $this->productRepository->findOneWhere([
            'sku' => $mainProduct['item_sku'] . '-P'
        ]);

        $productFlatData = $this->productFlatRepository->findOneWhere([
            'sku' => $mainProduct['item_sku'] . '-P'
        ]);

        if (empty($productData)) {
            $data['type'] = 'configurable';
            $data['attribute_family_id'] = $attributeFamilyData->id;
            $data['sku'] = $mainProduct['item_sku'] . '-P';

            $data['visible_individually'] = 1;
            $data['guest_checkout'] = 1;
            $data['status'] = 1;

            Event::dispatch('catalog.product.create.before');
            $configSimpleProduct = $this->productRepository->create($data);
            Event::dispatch('catalog.product.create.after', $configSimpleProduct);

            $configSimpleProduct->super_attributes()->attach(23);
            $configSimpleProduct->super_attributes()->attach(24);

            $productData = $configSimpleProduct;
        }

        $attributeMappedCodes = $this->attributesMapper();
        $attributesData = [];

        foreach ($productData->getTypeInstance()->getEditableAttributes()->toArray() as $key => $value) {
            $searchIndex = strtolower($value['code']);

            if ($searchIndex == 'tax_category_id') {
                continue;
            }

            if ($value['type'] == "select") {
                continue;
            }
            if ($value['type'] == "checkbox") {
                continue;
            }

            if (isset($attributeMappedCodes[$searchIndex])) {
                $attributesData[$searchIndex] = $mainProduct[$attributeMappedCodes[$searchIndex]];
            }
        }

        $attributesData['channel'] = core()->getCurrentChannel()->code;
        $attributesData['locale'] = 'en';

        $attributesData['url_key'] = Str::slug($attributesData['name']);

        $productData->getTypeInstance()->update($attributesData, $productData->id);

        $this->createVariants($variants, $productData, $attributesData);
    }

    protected function createVariants($variants, $product, $attributesData): void
    {
        $colorAttribute = $this->attributeRepository->findOneWhere([
            'code' => 'color'
        ]);

        $sizeAttribute = $this->attributeRepository->findOneWhere([
            'code' => 'size'
        ]);

        $data = [];
        $attributesValuesData = [];

        foreach ($variants as $key => $variant) {
            $sizeAttributeValue = $this->getOrCreateSizeAttributeOption($sizeAttribute, $variant);
            $colorAttributeValue = $this->getOrCreateColorAttributeOption($colorAttribute, $variant);

            $data['variants']['variant_' . $key] = [
                "sku" => $variant['item_sku'],
                "name" => $variant['item_name'],
                "price" => $variant['standard_price'],
                "weight" => $variant['package_weight'],
                "status" => 1,
                "color" => $colorAttributeValue->id,
                "size" => $sizeAttributeValue->id,
                "inventories" => [
                    1 => $variant['quantity']
                ]
            ];

            $attributesValuesData[] = [
                'color' => $colorAttributeValue,
                'size' => $sizeAttributeValue
            ];
        }

        $product->getTypeInstance()->update(array_merge($attributesData, $data), $product->id);

        $this->bulkProductRepository->productRepositoryUpdateForVariants(
            $attributesData,
            $product,
            [$colorAttribute, $sizeAttribute],
            $attributesValuesData
        );

        $product->refresh();
    }

    public function getOrCreateColorAttributeOption($colorAttribute, $variant)
    {
        $colorAttributeOption = $this->getAttributeOption($colorAttribute, $variant['color_name']);

        if (is_null($colorAttributeOption)) {
            $attributeData = [
                "isNew" => "true",
                "isDelete" => "",
                "admin_name" => $variant['color_name'],
                "en" => [
                    "label" => $variant['color_name']
                ]
            ];

            $this->attributeRepository->update([
                'type' => $colorAttribute->type,
                'options' => [
                    $attributeData
                ]
            ], $colorAttribute->id);

            $colorAttributeOption = $this->getAttributeOption($colorAttribute, $variant['color_name']);;
        }

        return $colorAttributeOption;
    }

    protected function getOrCreateSizeAttributeOption($sizeAttribute, $variant)
    {
        $sizeAttributeOption = $this->getAttributeOption($sizeAttribute, $variant['size_name']);
        if (is_null($sizeAttributeOption)) {
            $attributeData = [
                "isNew" => "true",
                "isDelete" => "",
                "admin_name" => $variant['size_name'],
                "en" => [
                    "label" => $variant['size_name']
                ]
            ];

            $this->attributeRepository->update([
                'type' => $sizeAttribute->type,
                'options' => [
                    $attributeData
                ]
            ], $sizeAttribute->id);

            $sizeAttributeOption = $this->getAttributeOption($sizeAttribute, $variant['size_name']);
        }

        return $sizeAttributeOption;
    }

    protected function getAttributeOption($attribute, $value)
    {
        return $this->attributeOptionRepository->findOneWhere([
            'attribute_id' => $attribute->id,
            'admin_name' => $value
        ]);
    }

    protected function attributesMapper(): array
    {
        return [
            'short_description' => 'product_description',
            'description' => 'product_description',
            'name' => 'item_name',
            'meta_title' => 'item_name',
            'brand' => 'brand_name',
            'bullet_point1' => 'bullet_point1',
            'bullet_point2' => 'bullet_point2',
            'bullet_point3' => 'bullet_point3',
            'bullet_point4' => 'bullet_point4',
            'bullet_point5' => 'bullet_point5',
            'bullet_point6' => 'bullet_point6',
        ];
    }
}
