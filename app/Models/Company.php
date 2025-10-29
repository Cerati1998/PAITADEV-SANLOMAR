<?php

namespace App\Models;

use App\Observers\CompanyObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

#[ObservedBy(CompanyObserver::class)]
class Company extends Model
{
    use HasApiTokens;
    use HasFactory;

    protected $fillable = [
        'ruc', 
        'razonSocial',
        'nombreComercial',
        'direccion',
        'detraction_account',
        'ubigeo',
        'sol_user',
        'sol_pass',
        'client_id',
        'client_secret',
        'certificate',
        'logo_path',

        'invoice_header',
        'invoice_footer',

        'production',
    ];

    protected $casts = [
        'production' => 'boolean',
    ];

    //Mutators and Accessors
    /* protected function logo():Attribute
    {
        return new Attribute(
            get: fn() => $this->logo_path ? Storage::url($this->logo_path) : asset('img/no-image.jpg'),
        );
    } */

    protected function logo():Attribute
    {
        return new Attribute(
            get: fn() => $this->logo_path ? Storage::url($this->logo_path) : asset('img/logos/paitadev.png'),
        );
    }

    //Relaciones uno a muchos
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    //Relaciones uno a muchos inversa
    public function district()
    {
        return $this->belongsTo(District::class, 'ubigeo', 'id');
    }

    //Relaciones muchos a muchos
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }
    
}
