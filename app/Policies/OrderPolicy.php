namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function view(User $user, Order $order)
    {
        // Admin can view any order
        if ($user->isAdmin()) {
            return true;
        }
        
        // Customer can only view their own orders
        return $user->id === $order->user_id;
    }
}