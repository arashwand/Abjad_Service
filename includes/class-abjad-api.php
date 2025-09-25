<?php
class Abjad_API {
    
    private $api_base_url;
    
    public function __construct() {
        $this->api_base_url = get_option('abjad_api_url', ABJAD_API_BASE_URL);
    }
    
    public function execute_service($user_id, $service_key, $input_data) {
        // دریافت توکن کاربر
        $token = $this->get_user_token($user_id);
        if (!$token) {
            return array('success' => false, 'error' => 'توکن معتبر یافت نشد');
        }
        
        // ارسال درخواست به سرویس ASP.NET
        $response = wp_remote_post($this->api_base_url . '/services/' . $service_key, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ),
            'body' => json_encode(array(
                'text' => $input_data,
                'userId' => $user_id,
                'timestamp' => current_time('timestamp')
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => 'خطا در ارتباط با سرویس');
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code === 200) {
            return array('success' => true, 'data' => $body);
        } else {
            return array('success' => false, 'error' => $body['error'] ?? 'خطای ناشناخته');
        }
    }
    
    public function create_license($license_data) {
        $response = wp_remote_post($this->api_base_url . '/license/create', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($license_data),
            'timeout' => 30
        ));
        
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    public function validate_license($token) {
        $response = wp_remote_get($this->api_base_url . '/license/validate/' . $token, array(
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return false;
        }
        
        return json_decode(wp_remote_retrieve_body($response), true);
    }
    
    private function get_user_token($user_id) {
        return get_user_meta($user_id, 'abjad_service_token', true);
    }

    public function validate_connection() {
    // تست ساده اتصال به API
    $response = wp_remote_get($this->api_base_url . '/health', array(
        'timeout' => 10
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    return $status_code === 200;
}

}
?>