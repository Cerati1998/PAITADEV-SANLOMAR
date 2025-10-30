<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\inspections\EirInspection;
use App\Models\inspections\LeakTest;
use App\Models\works\WorkOrder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'tipoDoc',
        'numDoc',
        'password',
        'profile_photo_path'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    protected function branch(): Attribute
    {
        return new Attribute(
            get: fn() => $this->branches
                ->where('company_id', session('company')->id)
                ->first(),
        );
    }

    //Relacion muchos a muchos
    public function companies()
    {
        return $this->belongsToMany(Company::class)
            ->withTimestamps();
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_company_user')
            ->withPivot('company_id')
            ->withTimestamps();
    }

    public function dischargesCreated()
    {
        return $this->hasMany(Dischargue::class, 'created_by');
    }

    public function dischargesAnulated()
    {
        return $this->hasMany(Dischargue::class, 'anulated_by');
    }

    public function identity()
    {
        return $this->belongsTo(Identity::class, 'tipoDoc');
    }
    public function devolutionsCreated()
    {
        return $this->hasMany(Devolution::class, 'created_by');
    }
    public function devolutionsAnulated()
    {
        return $this->hasMany(Devolution::class, 'anulated_by');
    }

    public function containerOperationalTraces()
    {
        return $this->hasMany(ContainerOperationalTrace::class, 'final_status_user');
    }

    public function branchIntegrations()
    {
        return $this->hasMany(BranchIntegration::class, 'created_by');
    }

    public function inspectionsOpen()
    {
        return $this->hasMany(EirInspection::class, 'inspector_id');
    }
    public function inspectionLeakTest()
    {
        return $this->hasMany(LeakTest::class, 'inspector_id');
    }
    public function inspectionsStimated()
    {
        return $this->hasMany(EirInspection::class, 'stimated_by');
    }
    public function inspectionsVoided()
    {
        return $this->hasMany(EirInspection::class, 'voided_by');
    }

    public function workOrderOpened()
    {
        return $this->hasMany(WorkOrder::class, 'opened_by');
    }
    public function workOrderClosed()
    {
        return $this->hasMany(WorkOrder::class, 'closed_by');
    }
}
