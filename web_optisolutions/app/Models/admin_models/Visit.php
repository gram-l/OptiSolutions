<?php
// app/Models/admin_models/Visit.php
namespace App\Models\admin_models;

use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    protected $table = 'visits'; // adjust if your table name differs
    protected $primaryKey = 'visit_id';
    public $timestamps = false;

    protected $fillable = [
        'doctor_id', 'patient_id', 'service_type', 'visit_date', 'notes',
    ];
}