<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QrDataCurl
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $returner;

    /**
     * Create a new event instance.
     */
    public function __construct($domain, $entry_id)
    {
        $this->returner = $this->fetchEntryData($domain, $entry_id);
    }

    protected function generateToken($domain) 
    {
        $secret_key = 'CvmJtiPdohSGs926';
        return hash_hmac('sha256', $domain, $secret_key);
    }


    protected function fetchEntryData($domain, $entry_id)
    {   
        $mh = curl_multi_init();
        $curl_handles = array();

        $all_response = array();

        foreach ($domain as $single_domain) {    
            if (empty($single_domain)){
                continue;
            }

            $token = $this->generateToken($single_domain->domain);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://' . $single_domain->domain . '/wp-content/plugins/custom-element/other/scanner_output.php');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['entry_id' => $entry_id]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Authorization: ' . $token,
            ));

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            curl_multi_add_handle($mh, $ch);
            $curl_handles[] = $ch;
        }
        
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        foreach ($curl_handles as $ch_key => $ch) {

            if (curl_errno($ch)) {
                echo 'Błąd: ' . curl_error($ch) . PHP_EOL;
            } else {
                // $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                // switch ($status) {
                //     case '301':
                //         echo $all_fairs[$ch_key][2] . ' status 301 => moved permanently <br><br>';
                //         $all_fairs_entries[$all_fairs[$ch_key][2] . "_" . $fair_year] = 'redirected';
                //         continue 2;
                //     case '403':
                //         echo $all_fairs[$ch_key][2] . ' status 403 -> forbidden access <br><br>';
                //         $all_fairs_entries[$all_fairs[$ch_key][2] . "_" . $fair_year] = 'forbiden';
                //         continue 2;
                //     case '200':
                //         break;
                //     default: 
                //         echo $all_fairs[$ch_key][2] . ' status ' . $status . ' => error <br><br>';
                //         continue 2;
                // }
                $decoded = json_decode(curl_multi_getcontent($ch));
                if (!empty($decoded)) {
                    $all_response = $decoded;
                }
                // $fair_year = (!empty($array['year'])) ? $array['year'] : date('Y');

                // $all_fairs_entries[$all_fairs[$ch_key][2] . "_" . $fair_year] = $array['data'];
                // $all_fairs_forms[$all_fairs[$ch_key][2] . "_" . $fair_year] = $array['forms'];
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        curl_multi_close($mh);
        return $all_response;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
