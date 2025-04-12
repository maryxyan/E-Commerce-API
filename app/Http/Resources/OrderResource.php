namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'total_price' => (float) $this->total_price,
            'status' => $this->status,
            'shipping_address' => $this->shipping_address,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                    ],
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->price,
                    'total' => (float) ($item->price * $item->quantity),
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}