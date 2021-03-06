<?php

namespace App\Imports;

use App\Customer;
use Platform\Models\History;
use Platform\Controllers\Core;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CustomerImport implements ToCollection, WithChunkReading, ShouldQueue
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */

    public function collection(Collection $rows)
    {
        $account = app()->make('account');
        $campaign = \Platform\Models\Campaign::withoutGlobalScopes()->whereId(3)->firstOrFail();

        foreach ($rows as $row)
        {
            $verification_code = Str::random(32);
            $customer_number = Core\Secure::getRandom(9, '1234567890');
            $locale = config('system.default_language');
            app()->setLocale($locale);
            $language = config('system.default_language');
            $timezone = config('system.default_timezone');

            $gender = 1;
            if($row[4] == 'female') {
              $gender = 0;
            }

            $email = $row[3];
            if(empty($row[3]) or $row[3] === '' or $row[3] === ' ' or $row[3] === 0) {
              $email = $customer_number.'@unique-seafood.com.my';
            }

            if (!$row->filter()->isEmpty()) {
            $user = new Customer;
            $user->account_id = $account->id;
            $user->campaign_id = $campaign->id;
            $user->created_by = $campaign->created_by;
            $user->role = 1;
            $user->active = 1;
            $user->customer_number = $customer_number;
            $user->name = $row[1];
            $user->email = $email;
            $user->password = bcrypt('Hello123');
            $user->language = 'en-gb';
            $user->locale = 'en-GB';
            $user->timezone = 'Asia/Kuala_Lumpur';
            $user->signup_ip_address = '192.168.10.1';
            $user->verification_code = $verification_code;
            $user->gender = $gender;
            $user->phone = $row[2];
            $user->dob = $row[5];
            $user->save();

            $this->ensureNumberIsUnique($user);

            if ($row[6] > 0) {
              $history = new History;

              $history->customer_id = $user->id;
              $history->campaign_id = $campaign->id;
              $history->created_by = $campaign->created_by;
              $history->event = 'Points - Carry Forward';
              $history->points = $row[6];
              $history->save();
            }

        }
      }
    }

    public function ensureNumberIsUnique(Customer $customer) {
        $user = Customer::where('id', '<>', $customer->id)->where('created_by', $customer->created_by)->where('customer_number', $customer->customer_number)->first();
        if ($user === null) {
          return true;
        } else {
          $customer_number = Core\Secure::getRandom(9, '1234567890');
          $customer->customer_number = $customer_number;
          $customer->save();
          $this->ensureNumberIsUnique($customer);
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
