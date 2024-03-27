<?php

declare(strict_types=1);

namespace Erathrive\Catalog\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Repositories\ProductAttributeValueRepository;

class BulkProductRepository extends Repository
{

    protected ProductAttributeValueRepository $productAttributeValueRepository;

    public function __construct(
        Application $app,
        ProductAttributeValueRepository $productAttributeValueRepository
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;

        parent::__construct($app);
    }

    public function model(): string
    {
        return \Webkul\Product\Contracts\Product::class;
    }

    public function productRepositoryUpdateForVariants(array $data, $product, array $attributes, array $values): void
    {
        Event::dispatch('catalog.product.update.before', $product->id);

        foreach ($attributes as $attribute) {
            $attributeValue = $this->productAttributeValueRepository->findOneWhere([
                'product_id'    => $product->id,
                'attribute_id'  => $attribute->id,
                'channel'       => $attribute->value_per_channel,
                'locale'        => $attribute->value_per_locale
            ]);

            if (is_null($attributeValue)) {
                foreach ($values as $value) {
                    $value = $value[$attribute->code];
                    $this->productAttributeValueRepository->create([
                        'product_id'    => $product->id,
                        'attribute_id'  => $attribute->id,
                        'text_value'    => $value->admin_name,
                        'channel'       => $attribute->value_per_channel ? $data['channel'] : null,
                        'locale'        => $attribute->value_per_locale ? $data['locale'] : null
                    ]);
                }
            }
        }

        Event::dispatch('catalog.product.update.after', $product);
    }
}
