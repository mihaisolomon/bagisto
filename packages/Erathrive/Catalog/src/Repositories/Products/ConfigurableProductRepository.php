<?php

declare(strict_types=1);

namespace Erathrive\Catalog\Repositories\Products;

use Illuminate\Container\Container as Application;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductRepository;

class ConfigurableProductRepository extends Repository
{
    protected AttributeFamilyRepository $attributeFamilyRepository;

    protected AttributeOptionRepository $attributeOptionRepository;

    protected ProductRepository $productRepository;

    public function __construct(
        Application $app,
        AttributeFamilyRepository $attributeFamilyRepository,
        AttributeOptionRepository $attributeOptionRepository,
        ProductRepository $productRepository
    ) {

        $this->attributeFamilyRepository = $attributeFamilyRepository;

        $this->attributeOptionRepository = $attributeOptionRepository;

        $this->productRepository = $productRepository;

        parent::__construct($app);
    }

    public function model(): string
    {
        return \Webkul\Product\Contracts\Product::class;
    }

    public function crateProduct(array $data): void
    {
        $attributeFamilyData = $this->attributeFamilyRepository->findOneByfield('name', 'Default');

        $productData = $this->productRepository->findOneWhere([
            'sku'   => $data[0]['item_sku'] . '-P'
        ]);

        if (empty($productData)) {
            $data['type'] = 'configurable';
            $data['attribute_family_id'] = $attributeFamilyData->id;
            $data['sku'] = $data[0]['item_sku'] . '-P';
            Event::dispatch('catalog.product.create.before');
            $configSimpleProduct = $this->productRepository->create($data);
            Event::dispatch('catalog.product.create.after', $configSimpleProduct);
        }

        $attributeCode = [];
        foreach ($productData->getTypeInstance()->getEditableAttributes()->toArray() as $key => $value) {
            $searchIndex = strtolower($value['code']);

            array_push($attributeCode, $searchIndex);

            if ($searchIndex == 'tax_category_id') {
                continue;
            }

//            if ($value['type'] == "select") {
//                dd($value);
//            }
        }

        dd($attributeCode);

    }

    protected function attributesMapper(): array
    {
        return [
            'short_description' => 'product_description',
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
