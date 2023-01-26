<?php

namespace App\Repositories;

use App\Models\Administrator;
use App\Models\Client;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Partner;
use App\Models\PartnerOperator;
use App\Models\User;

class UserRepository
{
    /**
     * @var User
     */
    protected $user;

    function __construct(User $user)
    {
        $this->user = $user;
    }

    public function save($data)
    {
        $user = new $this->user;
        $user->telegram_id = $data['telegram_id'];
        $user->name = $data['first_name'];
        $user->save();
        $client = $user->client()->create([
            'name' => $data['first_name'],
            'username' => $data['username'] ?? null,
        ]);


        $user->client_id = $client->id;
        $user->save();
        return $user->fresh();
    }
    public function create($data)
    {
        $role = $data['role'] ?? 'user';
        $name = $data['name'] ?? '-';

        switch ($role){
            case 'operator': $operator = Operator::create([
                'name' => $name,
                'activation_code' => $data['activation_code'],
            ]);
                break;
            case 'driver': $driver = Driver::create([
                'name' => $name,
                'activation_code' => $data['activation_code'],
            ]);
                break;
            case 'administrator': $administrator = Administrator::create([
                'name' => $name,
                'activation_code' => $data['activation_code'],
            ]);
                break;
            case 'partner_operator': $partner_operator = PartnerOperator::create([
                'name' => $name,
                'activation_code' => $data['activation_code'],
            ]);
                break;
            case 'partner': $partner_operator = Partner::create([
                'name' => $name,
                'activation_code' => $data['activation_code'],
            ]);
                break;
            default: $client = Client::create([
                'name' => $name,
            ]);
        }
        return true;
    }
    public function update($userID, $data)
    {
        $user = $this->getByTelegram($userID);
        if ($user->count() !== 1) {
            return false;
        }
        $user = $user->first();

        $user->update($data);

        return $user->refresh();
    }

    public function find($id)
    {
        return User::find($id);
    }

    public function getByTelegram($id)
    {
        return $this->user
            ->with(['cart'])
            ->where('telegram_id', $id)
            ->get();
    }
    public function getByPhone($phone)
    {
        return Client::with(['orders'])
            ->where('phone_number', 'LIKE', "%$phone%")
            ->get();
    }

    public function getByActivationCode($activation_code)
    {
        return $this->user
            ->where('activation_code', $activation_code)
            ->get();
    }

    public function getOperatorByActivationCode($activation_code)
    {
        return Operator::query()
            ->where('activation_code', $activation_code)
            ->get();
    }

    public function getDriverByActivationCode($activation_code)
    {
        return Driver::query()
            ->where('activation_code', $activation_code)
            ->get();
    }
    public function getPartnerByActivationCode($activation_code)
    {
        return Partner::query()
            ->where('activation_code', $activation_code)
            ->get();
    }
    public function getPartnerOperatorByActivationCode($activation_code)
    {
        return PartnerOperator::query()
            ->where('activation_code', $activation_code)
            ->get();
    }

    public function getPartnerOperators()
    {
        return PartnerOperator::query()
            ->with('user')
            ->get();
    }
    public function getPartners()
    {
        return Partner::query()
            ->whereNotNull('self_status')
            ->get();
    }
    public function getOperators()
    {
        return Operator::query()
            ->with('user')
            ->whereNotNull('self_status')
            ->get();
    }

    public function getByRole(string $role = 'user', $is_active = false)
    {
        if ($is_active){
            return $this->user
                ->where('role', $role)
                ->where('status', 'active')
                ->where('self_status', 'active')
                ->get();

        }else{
            return $this->user
                ->where('role', $role)
                ->get();
        }
    }
    public function getAvailableRestaurantEmployees()
    {
            return $this->user
                ->where('role', 'partner_operator')
                ->whereHas('partner_operator', function ($q){
                    return $q->whereNull('restaurant_id');
                })
                ->get();
    }
    public function getAll($except = 'user')
    {
        return $this->user->exceptByRole($except)->get();
    }
}
