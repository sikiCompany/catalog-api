<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_formatted' => 'R$ ' . number_format($this->price, 2, ',', '.'),
            'category' => $this->category,
            'status' => $this->status,
            'status_label' => $this->status === 'active' ? 'Ativo' : 'Inativo',
            'created_at' => $this->created_at?->toISOString(),
            'created_at_formatted' => $this->created_at?->format('d/m/Y H:i:s'),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'image_url' => $this->image_url ?? null,
            
            // Links HATEOAS
            'links' => [
                'self' => route('products.show', $this->id),
                'update' => route('products.update', $this->id),
                'delete' => route('products.destroy', $this->id),
                'image' => route('products.upload-image', $this->id)
            ]
        ];
    }
}