<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Dyrynda\Database\Support\GeneratesUuid;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

use App\Scopes\AccountScope;

class Customer extends Authenticatable implements JWTSubject, HasMedia
{
    use GeneratesUuid;
    use Notifiable;
    use HasMediaTrait;

    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'name', 'email', 'password',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $appends = [
      'account_active', 'avatar', 'points', 'campaign_text', 'number', 'events'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
      'id', 'campaign_id', 'history', 'campaign', 'events', 'password', 'remember_token', 'account', 'global_points', 'account_id', 'remote_customer_id', 'previous_remote_customer_id', 'domain', 'company', 'company_slug', 'files_dir', 'verification_code', 'token', 'login_code', 'login_code_valid_until', 'signup_ip_address', 'logins', 'last_login', 'last_login_ip_address', 'expires', 'customer_number', 'lead_source', 'lead_source_module', 'lead_status', 'lead_type', 'created_by', 'updated_by', 'created_at', 'salutation', 'first_name', 'last_name', 'job_title', 'date_of_birth', 'phone', 'mobile', 'website', 'fax', 'street1', 'street2', 'city', 'state', 'postal_code', 'country_code', 'zoom', 'lat', 'lng', 'primary_account', 'email_verified_at', 'hourly_rate', 'notes', 'additional_fields', 'settings', 'tags', 'attributes', 'meta', 'minus_sign', 'account_active', 'bank', 'bank_id', 'currency_code', 'ecode_swift', 'first_day_of_week', 'iban', 'id_number', 'id', 'media', 'shipping_city', 'shipping_country_code', 'shipping_lat', 'shipping_lng', 'shipping_postal_code', 'shipping_state', 'shipping_street1', 'shipping_street2', 'shipping_zoom', 'vat_number', 'gender', 'mobile_business', 'mobile_personal', 'phone_business', 'phone_personal', 'social'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'uuid' => 'uuid',
      'email_verified_at' => 'datetime',
      'social' => 'json',
      'settings' => 'json',
      'tags' => 'json',
      'attributes' => 'json',
      'meta' => 'json'
    ];

    public function registerMediaCollections() {
      $this
        ->addMediaCollection('avatar')
        ->singleFile();
    }

    public function registerMediaConversions(Media $media = null) {
        $this
          ->addMediaConversion('avatar')
          ->width(512)
          ->height(512)
          ->performOnCollections('avatar');
    }
    
    public function getJWTIdentifier() {
      return $this->getKey();
    }
    
    public function getJWTCustomClaims() {
      return [];
    }
    
    public static function boot() {
      parent::boot();

      if (auth()->check()) {
        static::addGlobalScope(new AccountScope(auth()->user()));
      }

      // On select
      static::retrieved(function ($model) {
      });

      // On update
      static::updating(function ($model) {
        if (auth()->check()) {
          $model->updated_by = auth()->user()->id;
        }
      });

      // On create
      self::creating(function ($model) {
        if (auth()->check()) {
          $model->account_id = auth()->user()->account_id;
          $model->created_by = auth()->user()->id;
        }
      });
    }

    /**
     * Form for creating a new record, per role
     * Note: if a column is required, make sure to add 'required' => true 
     *
     * @return array
     */
    public static function getCreateForm() {
      $owner = [
        'tab1' => [
          'text' => __('General'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'text', 'column' => 'name', 'text' => __('Name'), 'validate' => 'required|max:32', 'required' => true],
                ['type' => 'email', 'column' => 'email', 'text' => __('E-mail address'), 'validate' => 'required|email|max:64', 'required' => true],
                ['type' => 'password', 'column' => 'password', 'text' => __('Password'), 'hint_edit' => __('Leave blank to keep current password'), 'validate' => 'nullable|min:8|max:32', 'validate_create' => 'required|min:8|max:32', 'required_create' => true],
                ['type' => 'image', 'image' => ['thumb_width' => '140px', 'thumb_height' => '140px'], 'column' => 'avatar', 'text' => __('Avatar'), 'validate' => 'nullable', 'icon' => 'account_circle', 'class' => 'img-rounded my-3'],
                ['type' => 'boolean', 'default' => true, 'column' => 'active', 'text' => __('Active'), 'validate' => 'nullable']
              ]
            ]
          ]
        ]
      ];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Name used in plan limitations (optional)
     *
     * @return string
     */
    public static function getLimitationName() {
      return 'customers';
    }

    /**
     * Export class name if table can be exported (optional)
     *
     * @return string
     */
    public static function getExportClass() {
      $owner = null;
      $reseller = null;
      $user = 'Platform\Exports\CustomersExport';

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Columns used for filters
     *
     * @return array
     */
    public static function getTableFilters() {
      $owner = [
        ['column' => 'campaign_id', 'text' => __('All campaigns'), 'icon' => 'filter_list', 'type' => 'relation', 'default' => null, 'relation' => ['type' => 'hasOne', 'permission' => 'personal', 'with' => 'campaign', 'table' => 'campaigns', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc']]
      ];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Extra columns used in select queries, exposed in json response
     *
     * @return array
     */
    public static function getExtraSelectColumns() {
      $owner = ['uuid'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Extra columns used in select queries, hidden from json response
     *
     * @return array
     */
    public static function getExtraQueryColumns() {
      $owner = ['id', 'created_by', 'campaign_id', 'customer_number'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Generic settings
     *
     * actions: add actions column (true / false)
     *
     * @return array
     */
    public static function getSettings() {
      $owner = ['select_all' => true, 'actions' => true, 'create' => false, 'actions_width' => '90px'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Language variables
     *
     * @return array
     */
    public static function getTranslations() {
      return [
        'items' => __('Customers'),
        'edit_item' => __('Edit customer'),
        'create_item' => __('Create customer'),
      ];
    }

    /**
     * Define per role if and what they can see
     *
     * all: all records from all accounts
     * account: all records from the current account
     * personal: only records the current user has created
     * created_by: only records created by the user id defined like created_by:1
     * none: this role has no permission
     *
     * @return array
     */
    public static function getPermissions() {
      $owner = ['view' => 'all', 'delete' => 'all', 'update' => 'all', 'create' => false];
      $reseller = ['view' => 'account', 'delete' => 'account', 'update' => 'account', 'create' => false];
      $user = ['view' => 'personal', 'delete' => 'personal', 'update' => 'personal', 'create' => false];

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * The headers for the data table, per role
     *
     * @return array
     */
    public static function getHeaders() {
      $owner = [
        ['visible' => true, 'value' => 'avatar', 'exclude_from_select' => true, 'width' => '60px', 'text' => __('Avatar'), 'align' => 'left', 'sortable' => false],
        ['visible' => true, 'value' => 'name', 'text' => __('Name'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'email', 'text' => __('E-mail'), 'align' => 'left', 'sortable' => true],
        /*['visible' => true, 'value' => 'locale', 'type' => 'locale', 'text' => __('Locale'), 'align' => 'left', 'sortable' => true],*/
        ['visible' => true, 'value' => 'points', 'exclude_from_select' => true, 'type' => 'number', 'text' => __('Points'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'number', 'exclude_from_select' => true, 'text' => __('Customer number'), 'align' => 'left', 'sortable' => false],
        ['visible' => true, 'value' => 'campaign_text', 'exclude_from_select' => true, 'relation' => ['type' => 'hasOne', 'permission' => 'personal', 'with' => 'campaign', 'table' => 'campaigns', 'val' => 'name'], 'text' => __('Campaign'), 'align' => 'left', 'sortable' => false],
        ['visible' => true, 'value' => 'updated_at', 'type' => 'date_time', 'format' => 'ago', 'text' => __('Last activity'), 'align' => 'left', 'sortable' => true, 'default_order' => true],
        ['visible' => true, 'value' => 'active', 'text' => __('Active'), 'align' => 'center', 'sortable' => true, 'type' => 'boolean']
      ];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * The columns used for searching the table
     *
     * @return array
     */
    public static function getSearchColumns() {
      $owner = ['name', 'email', 'customer_number'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Available actions for data table row, per role
     *
     * @return array
     */
    public static function getActions() {
      $owner = [
        ['text' => __('Edit'), 'action' => 'edit', 'icon' => 'edit', 'color' => 'secondary', 'dark' => false],
        /*['divider'],*/
        ['text' => __('Delete'), 'action' => 'delete', 'icon' => 'delete', 'color' => 'secondary', 'dark' => true]
      ];

      $reseller = [
        ['text' => __('Edit'), 'action' => 'edit', 'icon' => 'edit', 'color' => 'secondary', 'dark' => false]
      ];

      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Get campaign points.
     *
     * @return integer
     */
    public function getPointsAttribute() {
      return $this->history->sum('points');
    }

    /**
     * Get campaign name
     *
     * @return string
     */
    public function getCampaignTextAttribute() {
      return ($this->campaign != null) ? $this->campaign->name : null;
    }

    /**
     * Account is active.
     *
     * @return string
     */
    public function getAccountActiveAttribute() {
      if ($this->account !== null && $this->account->expires !== null) {
        return ! $this->account->expires->addDays(config('system.grace_period_days'))->isPast();
      } else {
        return true;
      }
    }

    /**
     * Format customer number.
     *
     * @return string
     */
    public function getNumberAttribute() {
      return implode('-', str_split($this->customer_number, 3));
    }

    /**
     * Get avatar.
     *
     * @return string for use in <img> src
     */
    public function getAvatarAttribute() {
      if ($this->getFirstMediaUrl('avatar') !== '') {
        return $this->getFirstMediaUrl('avatar', 'avatar');
      } else {
        return (string) \Avatar::create(strtoupper($this->name))->toBase64();
      }
    }

    /**
     * Get the customer's history
     */
    public function getHistory($locale = null) {
      $history = $this->history;

      if ($locale !== null) {
        app()->setLocale($locale);
      }

      $history = $history->map(function ($record) {
        $record->created_at = $record->created_at->timezone($this->getTimezone());
        $record->description = __($record->description);

        return collect($record)->only('color', 'created_at', 'description', 'icon', 'icon_size', 'points', 'reward_title');
      });

      return $history;
    }

    /**
     * Money formatting
     */
    public function formatMoney($amount, $currency = 'USD', $formatHtml = false) {
      if ($currency == null) $currency = 'USD';
      $value = Money::{$currency}($amount);
      $currencies = new \Money\Currencies\ISOCurrencies();

      $numberFormatter = new \NumberFormatter($this->getLanguage(), \NumberFormatter::CURRENCY);
      $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

      $amount = $moneyFormatter->format($value);
      if ($formatHtml) {
        $amount = explode($numberFormatter->getSymbol(0), $amount);
        $amount = $amount[0] . '<span class="cents">' . $numberFormatter->getSymbol(0) . $amount[1] . '</span>';
      }

      return $amount;
    }

    /**
     * Date / time formatting
     */
    public function formatDate($date, $format = 'date_medium') {
      if ($date !== null) {
        switch ($format) {
          case 'date_medium': $date = $date->timezone($this->getTimezone())->format($this->getUserDateFormat()); break;
          case 'datetime_medium': $date = $date->timezone($this->getTimezone())->format($this->getUserDateFormat() . ' ' . $this->getUserTimeFormat()); break;
          case 'friendly': $date = $date->timezone($this->getTimezone())->diffForHumans(); break;
        }
        return $date;
      } else {
        return null;
      }
    }

    /**
     * Check if user was online recently.
     *
     * @return boolean
     */
    public function getRecentlyOnline($minutes = 10) {
      $lastActivity = strtotime(\Carbon\Carbon::now()->subMinutes($minutes));
      $visit = \DB::table('sessions')
        ->whereRaw('user_id = ?', [$this->id])
        ->whereRaw('last_activity >= ?', [$lastActivity])
        ->first();

      return ($visit === null) ? false : true;
    }

    /**
     * User language
     */
    public function getLanguage() {
      if ($this->language === NULL) {
        return config('system.default_language');
      } else {
        return $this->language;
      }
    }

    /**
     * User locale
     */
    public function getLocale() {
      if ($this->locale === NULL) {
        $language = $this->getLanguage();
        // If there is no default for user's language, use global default
        return config('system.language_defaults.' . $language . '.locale', config('system.default_locale'));
      } else {
        return $this->locale;
      }
    }

    /**
     * User timezone
     */
    public function getTimezone() {
      if ($this->timezone === NULL) {
        $language = $this->getLanguage();
        // If there is no default for user's language, use global default
        return config('system.language_defaults.' . $language . '.timezone', config('system.default_timezone'));
      } else {
        return $this->timezone;
      }
    }

    /**
     * User currency
     */
    public function getCurrency() {
      if ($this->currency_code === NULL) {
        $language = $this->getLanguage();
        // If there is no default for user's language, use global default
        return config('system.language_defaults.' . $language . '.currency', config('system.default_currency'));
      } else {
        return $this->currency_code;
      }
    }

    /**
     * Relationships
     * -------------
     */

    public function account() {
      return $this->belongsTo(\App\User::class, 'account_id', 'id');
    }

    public function campaign() {
      return $this->hasOne(\Platform\Models\Campaign::class, 'id', 'campaign_id');
    }

    public function history() {
      return $this->hasMany(\Platform\Models\History::class, 'customer_id', 'id')->orderBy('created_at', 'asc');
    }
}