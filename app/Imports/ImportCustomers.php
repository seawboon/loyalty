<?php

namespace App\Imports;

use App\Customer;
use Illuminate\Support\Str;
use Platform\Controllers\Core;
use Maatwebsite\Excel\Concerns\ToModel;

class ImportCustomers implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        $account = app()->make('account');
        $verification_code = Str::random(32);
        $customer_number = Core\Secure::getRandom(9, '1234567890');

        return new Customer([
          'account_id' => $account->id,
          'customer_number' => $customer_number,
          'name'     => $row[3],
          'email'    => $row[6],
          'password' => bcrypt('Hello123'),
          'verification_code' => $verification_code,
        ]);
    }
}
