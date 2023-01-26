<?php

namespace App\Http\Controllers\Bot\Services;

use App\Http\Controllers\Bot\Core\DTObject;
use App\Models\Driver;
use App\Models\Operator;
use App\Models\Partner;
use App\Models\PartnerOperator;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class UserService
{
    /**
     * @var $userRespository
     */
    protected $userRepository;

    /**
     * UserService constructor
     *
     * @param UserRepository $userRepository
     */

    function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function resetSteps(int $tg_id){
        $values = [
            'last_step' => null,
            'last_value' => null,
            'last_message_id' => null,
        ];

        $this->updateUserLastStep($tg_id, $values);
    }
    public function saveUserData(DTObject $dto)
    {
        $values = [
            'telegram_id' => $dto->telegram_id,
            'first_name' => $dto->first_name,
            'last_name' => $dto->last_name,
            'username' => $dto->username,
        ];

        $validator = Validator::make($values, [
            'telegram_id' => 'required|numeric',
            'first_name' => 'sometimes|string',
            'last_name' => 'nullable|string',
            'username' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return $this->userRepository->save($values);
    }
    public function createOperator(array $data)
    {
        return $this->userRepository->create(
            array_merge($data, ['role' => 'operator'])
        );
    }

    public function createDriver(array $data)
    {
        return $this->userRepository->create(
            array_merge($data, ['role' => 'driver'])
        );
    }

    public function createPartner(array $data)
    {
        return $this->userRepository->create(
            array_merge($data, ['role' => 'partner'])
        );
    }

    public function createPartnerOperator(array $data)
    {
        return $this->userRepository->create(
            array_merge($data, ['role' => 'partner_operator'])
        );
    }

    public function createAdministrator(array $data)
    {
        return $this->userRepository->create(
            array_merge($data, ['role' => 'administrator'])
        );
    }

    public function find($id)
    {
        $user = $this->userRepository->find($id);
        if ($user instanceof User){
            return $user;
        }
        return null;
    }
    public function findDriver($id)
    {
        $user = Driver::find($id);
        if ($user instanceof Driver){
            return $user;
        }
        return null;
    }
    public function findOperator($id)
    {
        $operator = Operator::find($id);
        if ($operator instanceof Operator){
            $operator->load('user');
            if ($operator instanceof Operator){
                return $operator;
            }
        }
        return null;
    }
    public function findPartner($id)
    {
        $user = Partner::find($id);
        if ($user instanceof Partner){
            return $user;
        }
        return null;
    }
    public function findPartnerOperator($id)
    {
        $user = PartnerOperator::find($id);
        if ($user instanceof PartnerOperator){
            return $user;
        }
        return null;
    }

    public function getByTelegramID($id)
    {
        $items = $this->userRepository->getByTelegram($id);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }

    public function searchClientsByPhone($phone)
    {
        $items = $this->userRepository->getByPhone($phone);
        if ($items->count() > 0) {
            return $items;
        }
        return new Collection();
    }

    public function getUserByActivationCode($activation_code)
    {
        $items = $this->userRepository->getByActivationCode($activation_code);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }

    public function getOperatorByActivationCode($activation_code)
    {
        $items = $this->userRepository->getOperatorByActivationCode($activation_code);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }
    public function getDriverByActivationCode($activation_code)
    {
        $items = $this->userRepository->getDriverByActivationCode($activation_code);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }
    public function getPartnerByActivationCode($activation_code)
    {
        $items = $this->userRepository->getPartnerByActivationCode($activation_code);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }

    public function getPartnerOperatorByActivationCode($activation_code)
    {
        $items = $this->userRepository->getPartnerOperatorByActivationCode($activation_code);
        if ($items->count() !== 1) {
            return null;
        }
        return $items->first();
    }

    public function getAllEmployeeIDs()
    {
        $items = $this->userRepository->getByRole('administrator');
        if ($items->count() > 0) {
            return $items->pluck('telegram_id');
        }
        return [];
    }

    public function getOperators($is_available = false)
    {
        $items = $this->userRepository->getOperators('operator');

        if ($items->count() > 0) {
            return $items;
        }
        return New Collection();
    }

    public function getDrivers($is_available = false)
    {
        $items = Driver::query()->with('user')->get();

        if ($items->count() > 0) {
            return $items;
        }
        return New Collection();
    }

    public function getActiveDrivers($is_available = false)
    {
        $items = $this->userRepository->getByRole( 'driver', $is_available);

        if ($items->count() > 0) {
            return $items;
        }
        return New Collection();
    }

    public function getPartnerOperators()
    {
        $items = $this->userRepository->getPartnerOperators('partner_operators');

        if ($items->count() > 0) {
            return $items;
        }
        return New Collection();
    }

    public function getPartners()
    {
        $items = $this->userRepository->getPartners('partner');

        if ($items->count() > 0) {
            return $items->pick('id', 'name');
        }
        return New Collection();
    }

    public function getActivePartners()
    {
        $items = $this->userRepository->getPartners('partner');

        $items = $items->filter(fn($item) => $item->status === 'active' && $item->self_status == true);

        if ($items->count() > 0) {
            return $items->pick('id', 'name');
        }
        return New Collection();
    }

    public function getActivePartnerOperators()
    {
        $items = $this->userRepository->getPartnerOperators('partner');

        $items = $items->filter(fn($item) => $item->status === 'active' && $item->self_status == true);

        if ($items->count() > 0) {
            return $items->pick('id', 'name');
        }
        return New Collection();
    }

    public function getAvailableRestaurantEmployees()
    {

        $items = $this->userRepository->getAvailableRestaurantEmployees();

        if ($items->count() > 0) {
            return $items->pick('id', 'name', 'restaurant_id');
        }
        return New Collection();
    }

    public function getAllEmployees()
    {
        $items = $this->userRepository->getByRole('administrator');
        $items = $items->merge($this->userRepository->getByRole('operator'));
        if ($items->count() > 0) {
            return $items;
        }
        return New Collection();
    }
    public function getAdministrators()
    {
        $items = $this->userRepository->getByRole('administrator');
        if ($items->count() > 0) {
            return $items;
        }
        return New Collection();
    }

    public function getUsers($role){
        return $this->userRepository->getAll($role);
    }

    public function updateUserLanguage($userID, $values)
    {
        // dd($values);
        $validator = Validator::make($values, [
            'language' => [
                'required',
                'in:ru,uz'
            ],

        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }

        return $this->userRepository->update($userID, $values);
    }
    public function updateUserLastStep($userID, $values)
    {
        return $this->userRepository->update($userID, $values);
    }

    public function updateContact($userID, $phone_number)
    {
        $params = [
            'phone_number' => $phone_number
        ];

        return $this->userRepository->update($userID, $params);
    }
    public function updateName($userID, $name)
    {
        $params = [
            'first_name' => $name
        ];

        return $this->userRepository->update($userID, $params);
    }
}
