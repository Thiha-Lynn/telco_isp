<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BankSetting extends Model
{
    protected $primaryKey = 'id';
    protected $table      = 'bank_settings';
    protected $fillable   = ['type', 'api_url', 'auth_token', 'ecommerce_id', 'sub_mer_id', 'mer_id', 'transaction_type', 'notifyurl', 'cb_status', 'cb_redirect', 'kbz_type', 'kbz_api_url', 'kbz_m_code', 'kbz_appid', 'kbz_key', 'kbz_trade_type', 'kbz_notifyurl', 'kbz_version', 'kbz_redirecct', 'kbz_status', 'aya_paytype', 'aya_api_tokenurl', 'aya_consumer_key', 'aya_consumer_secret', 'aya_grant_type', 'aya_api_baseurl', 'aya_phone', 'aya_password', 'aya_enc_key', 'aya_status', 'direct_type', 'direct_apiurl', 'direct_mcode', 'direct_key', 'direct_status', 'wave_live_seconds', 'wave_merchnt_id', 'wave_callback_url', 'wave_secret_key', 'wave_base_url', 'wave_status'];
 
}
