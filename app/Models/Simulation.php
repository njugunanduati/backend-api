<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{

    protected $table = 'simulations';
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'uuid',
        'client_ip_address',
        'annual_revenue',
        'gross_profit_margin',
        'net_profit_margin',
        'percentage_impact',
        'currency',
        'net_profit',
        'new_annual_profit',
        'coach_first_name',
        'coach_last_name',
        'coach_email',
        'coach_url',
        'cut_costs',
        'revenueincrease_cut_costs',
        'market_dominating_position',
        'revenueincrease_market_dominating_position',
        'compelling_offer',
        'revenueincrease_compelling_offer',
        'increase_prices',
        'revenueincrease_increase_prices',
        'upsell_cross_sell',
        'revenueincrease_upsell_cross_sell',
        'bundling',
        'revenueincrease_bundling',
        'downsell',
        'revenueincrease_downsell',
        'additional_products_services',
        'revenueincrease_additional_products_services_revenue',
        'drip_campaign',
        'revenueincrease_drip_campaign',
        'alliances_joint_ventures',
        'revenueincrease_alliances_joint_ventures',
        'more_leads',
        'revenueincrease_more_leads',
        'digital_marketing',
        'revenueincrease_digital_marketing',
        'content_marketing',
        'revenueincrease_content_marketing',
        'website_optimization',
        'revenueincrease_website_optimization',
        'email_marketing',
        'revenueincrease_email_marketing',
        'search_engine_optimization',
        'revenueincrease_search_engine_optimization',
        'digital_advertising',
        'revenueincrease_digital_advertising',
        'social_media',
        'revenueincrease_social_media',
        'video_marketing',
        'revenueincrease_video_marketing',
        'metrics_marketing',
        'revenueincrease_metrics_marketing',
        'strategy',
        'revenueincrease_strategy',
        'trust_expertise_education',
        'revenueincrease_trust_expertise_education',
        'policies_procedures',
        'revenueincrease_policies_procedures',
        'referral_systems',
        'revenueincrease_referral_systems',
        'publicity_pr',
        'revenueincrease_publicity_pr',
        'direct_mail',
        'revenueincrease_direct_mail',
        'advertising',
        'revenueincrease_advertising',
        'scripts',
        'revenueincrease_scripts',
        'initial_close_rate',
        'revenueincrease_initial_close_rate',
        'follow_up_close_rate',
        'revenueincrease_follow_up_close_rate',
        'sales_team',
        'revenueincrease_sales_team',
        'more_appointments',
        'revenueincrease_more_appointments',
        'increase_frequency_of_purchase',
        'revenueincrease_increase_frequency_of_purchase',
        'increase_longevity_of_buying_relationship',
        'revenueincrease_increase_longevity_buying_relationship',
        'sales_training',
        'revenueincrease_sales_training',
        'more_profitable_trade_shows',
        'revenueincrease_more_profitable_trade_shows',
        'dealing_with_decision_makers',
        'revenueincrease_dealing_with_decision_makers',
        'attracting_dream_clients',
        'revenueincrease_attracting_dream_clients',
        'order_fullfillment',
        'revenueincrease_order_fullfillment',
        'overcoming_buyers_remorse',
        'revenueincrease_overcoming_buyers_remorse',
        'simulation_date',
    ];


    // UUID Mutator
    public function setUuidAttribute($value)
    {
        $this->attributes['uuid'] = $this->convertStringUuidToBinary($value);
    }

    // UUID Accessor (Getter)
    public function getUuidAttribute($value)
    {
        return $this->convertBinaryUuidToString($value);
    }

    private function convertStringUuidToBinary($uuidString)
    {
        return pack('H*', str_replace('-', '', $uuidString));
    }

    private function convertBinaryUuidToString($binaryUuid)
    {
        $stringUuid = unpack('H*', $binaryUuid)[1];
        return substr($stringUuid, 0, 8) . '-' . substr($stringUuid, 8, 4) . '-' . substr($stringUuid, 12, 4) . '-' . substr($stringUuid, 16, 4) . '-' . substr($stringUuid, 20);
    }

    /**
     * Set the first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the last_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = trimSpecial(strip_tags($value));
    }
    /**
     * Set the coach_first_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setCoachFirstNameAttribute($value)
    {
        $this->attributes['coach_first_name'] = trimSpecial(strip_tags($value));
    }

    /**
     * Set the coach_last_name. remove html php xters
     *
     * @param  string  $value
     * @return void
     */
    public function setCoachLastNameAttribute($value)
    {
        $this->attributes['coach_last_name'] = trimSpecial(strip_tags($value));
    }
}
