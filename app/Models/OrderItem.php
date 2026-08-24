<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OrderItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'order_items';

    protected $fillable = [
        'customer_account_id',
        'branch_id',
        'order_id',
        'product_id',
        'selling_price',
        'quantity',
        'sub_total',
        'order_item_status', // 0=cancelled, 1=bought, 2=pending
        'created_by',
        'created_by_type',
        'date_created',
        'last_updated_by',
        'last_updated_by_type',
        'last_date_updated',
        'updated_by',
        'updated_by_type',
        'date_updated',
        'active', // 0=no, 1=yes
    ];

    protected $casts = [
        'selling_price' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'date_created' => 'datetime',
        'last_date_updated' => 'datetime',
        'date_updated' => 'datetime',
    ];

    /**
     * For UUID
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /*--------------------------------------------------------------
    | Relationships
    --------------------------------------------------------------*/

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerAccount::class, 'customer_account_id');
    }

    // ----------------------
    // Dynamic user accessors
    // ----------------------

    protected function resolveUser($id, $type)
    {
        if (!$id || !$type) return null;

        return match ($type) {
            'owner' => OwnerAccount::find($id),
            'staff' => StaffAccount::find($id),
            default => null,
        };
    }

    public function getCreatorAttribute()
    {
        return $this->resolveUser($this->created_by, $this->created_by_type);
    }

    public function getLastUpdatorAttribute()
    {
        return $this->resolveUser($this->last_updated_by, $this->last_updated_by_type);
    }

    public function getUpdatorAttribute()
    {
        return $this->resolveUser($this->updated_by, $this->updated_by_type);
    }
}