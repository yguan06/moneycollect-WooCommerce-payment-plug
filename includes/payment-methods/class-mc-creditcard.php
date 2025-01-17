<?php
if (! defined ( 'ABSPATH' ))
    exit (); // Exit if accessed directly

class WC_Gateway_Mc_Creditcard extends WC_MC_Payment_Gateway
{

    var $id;
    var $method_title = MONEYCOLLECT_NAME.' Credit Card';
    var $is_inline;
    var $icon_card = [
        'visa' => 'Visa',
        'mastercard' => 'MasterCard',
        'ae' => 'American Express',
        'jcb' => 'JCB',
        'discover' => 'Discover',
        'diners_club' => 'Diners Club',
        'maestro' => 'Maestro',
        'unionpay' => 'UnionPay',
    ];

    public function __construct()   {

        parent::__construct('moneycollect');
        $this->method_description = sprintf( __( 'If you don\'t have an account, please click <a target="_blank" href="%s">register</a>', 'moneycollect' ),'http://dev.merchants.moneycollect.com/register' );

        $this->is_inline = $this->get_option('checkout_model') === '1' ? 'yes' : 'no';
        $this->has_fields = $this->is_inline === 'yes'?true:false;
        $this->supports = [
            'products',
        ];

        if( $this->is_inline === 'yes' ){
            $this->supports[] = 'tokenization';
        }

        if( is_checkout() && $this->is_inline === 'yes' ){
            add_action( 'wp_enqueue_scripts', [ $this, 'checkout_scripts' ] );
        }

    }

    /** admin setting */
    public function init_form_fields(){
        $this->form_fields = [
            'enabled' => [
                'title' => __ ( 'Enable/Disable', 'moneycollect' ),
                'type' => 'checkbox',
                'label' => __ ( 'Enable Payment', 'moneycollect' ),
                'default' => 'no'
            ],
            'title' =>[
                'title' => __ ( 'Title', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'This controls the title which the user sees during checkout.', 'moneycollect' ),
                'default' => 'Credit Card',
                'desc_tip' => true,
            ],
            'description' =>[
                'title' => __ ( 'Description', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'This controls the description which the user sees during checkout.', 'moneycollect' ),
                'desc_tip' => true,
            ],
            'test_model' =>[
                'title' => __ ( 'Enable Test Mode', 'moneycollect' ),
                'type' => 'checkbox',
                'description' => __ ( 'Place the payment gateway in test mode using test API keys.', 'moneycollect' ),
                'default' => 'yes',
                'desc_tip' => true,
            ],

            'test_publishable_key' =>[
                'title' => __ ( 'Test Public Key', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'Get your API keys from your MoneyCollect account. Invalid values will be rejected. Only values starting with "test_pu_" will be saved.', 'moneycollect' ),
                'desc_tip' => true,
            ],
            'test_secret_key' =>[
                'title' => __ ( 'Test Private Key', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'Get your API keys from your MoneyCollect account. Invalid values will be rejected. Only values starting with "test_pr_" will be saved.', 'moneycollect' ),
                'desc_tip' => true,
            ],
            'publishable_key' =>[
                'title' => __ ( 'Live Public Key', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'Get your API keys from your MoneyCollect account. Invalid values will be rejected. Only values starting with "live_pu_" will be saved.', 'moneycollect' ),
                'desc_tip' => true,
            ],
            'secret_key' =>[
                'title' => __ ( 'Live Private Key', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'Get your API keys from your MoneyCollect account. Invalid values will be rejected. Only values starting with "live_pr_" will be saved.', 'moneycollect' ),
                'desc_tip' => true,
            ],

            'webhook' => [
                'title' => __( 'Webhook Endpoints', 'moneycollect' ),
                'type' => 'checkbox',
                'default' => 'yes',
                'description' => $this->display_admin_settings_webhook_description(),
            ],
            'pre_auth' => [
                'title' => __('Pre auth', 'moneycollect' ),
                'label' => __ ( 'Open pre auth', 'moneycollect' ),
                'type' => 'checkbox',
                'default' => 'no',
                'description' => __('Open pre auth', 'moneycollect'),
                'desc_tip' => true,
            ],
            'statement_descriptor' =>[
                'title' => __ ( 'Statement Descriptor', 'moneycollect' ),
                'type' => 'text',
                'description' => __ ( 'Statement descriptors only supports 5-22 alphanumeric characters, spaces, and these special characters: & , . - #, and it must contain at least one letter.' ),
                'desc_tip' => true,
            ],

            'checkout_model' => [
                'title' => __ ( 'Checkout model', 'moneycollect' ),
                'type' => 'select',
                'description' => __( 'Select the checkout model.', 'moneycollect' ),
                'default' => '1',
                'desc_tip'    => true,
                'options'     => [
                    '1' => __( 'In-page Checkout', 'moneycollect' ),
                    '2'  => __( 'Hosted Payment Page', 'moneycollect' ),
                ],
            ],
            'form_style' => [
                'title' => __ ( 'Form style', 'moneycollect' ),
                'type' => 'select',
                'description' => __( 'Select the form_style.', 'moneycollect' ),
                'default' => 'inner',
                'desc_tip'    => true,
                'options'     => [
                    'inner' => __( 'One row', 'moneycollect' ),
                    'block'  => __( 'Two rows', 'moneycollect' ),
                ],
            ],
            'save_card' =>[
                'title' => __ ( 'Saved Cards', 'moneycollect' ),
                'label' => __ ( 'Enable Payment via Saved Cards', 'moneycollect' ),
                'type' => 'checkbox',
                'description' => __ ( 'If enabled, users will be able to pay with a saved card during checkout. Card details are saved on MoneyCollect servers, not on your store.', 'moneycollect' ),
                'default' => 'no',
                'desc_tip' => true,
            ],

            'logging' =>[
                'title' => __ ( 'Logging', 'moneycollect' ),
                'label' => __ ( 'The log messages are saved at ', 'moneycollect' ).'<strong style="background-color:#ddd;">&nbsp;wp-content/uploads/wc-logs/money-collect-*.log&nbsp;</strong>',
                'type' => 'checkbox',
                'description' => __ ( 'Save debug messages to the WooCommerce System Status log.', 'moneycollect' ),
                'default' => 'no',
                'desc_tip' => true,
            ],
        ];
        foreach ($this->icon_card as $key => $val){
            $this->form_fields[$key] = [
                'title' => $val,
                'label' => "<div class='mc-icon'><img src='". MONEYCOLLECT_URL ."/assets/images/card/".$key.".png' /></div>",
                'type' => 'checkbox',
                'description' => sprintf(__ ( 'Show %s logo', 'moneycollect' ),$val),
                'default' =>  ($key === 'visa' || $key === 'mastercard') ? 'yes' : 'no',
                'desc_tip' => true,
            ];
        }
    }

    public function admin_options() {
        wp_enqueue_style( 'mc_styles', MONEYCOLLECT_URL.'/assets/css/mc_admin.css', [], MONEYCOLLECT_VERSION );
        wp_enqueue_script('mc_admin', MONEYCOLLECT_URL.'/assets/js/mc_admin.js', ['jquery'], MONEYCOLLECT_VERSION, true);
        parent::admin_options();
    }

    public function get_icon(){

        $img = '';
        foreach ($this->icon_card as $key => $value){
            if( $this->get_option($key) === 'yes' ){
                $img .= '<img class="wc-mc-icon-card" src="'.MONEYCOLLECT_URL.'/assets/images/card/'.$key.'.png" alt="'.$value.'" />';
            }
        }
        return apply_filters( 'woocommerce_gateway_icon', $img, $this->id );
    }

    public function payment_fields(){
        if( is_add_payment_method_page() ){
            echo apply_filters( 'wc_'.$this->id.'_description', __('New payment methods can only be added during checkout'), $this->id );
        }else{
            parent::payment_fields();
            if( $this->is_inline == 'yes' ){
                $this->elements_form();
            }
        }
    }

    function process_payment($order_id){

        if( $this->is_inline === 'no' ){
            return parent::process_payment($order_id);
        }

        $use_token = ( isset($_POST['wc-'.$this->id.'-payment-token']) && $_POST['wc-'.$this->id.'-payment-token'] != 'new' )? true: false;
        $new_card = isset($_POST['mc_payment_method_id'])? true: false;
        $save_card = isset($_POST['wc-'.$this->id.'-new-payment-method'])? true: false;

        $this->order = new WC_Order($order_id);

        $pm_id = '';

        if( $use_token ){
            $token_id = sanitize_text_field($_POST['wc-'.$this->id.'-payment-token']);
            $wc_token = WC_Payment_Tokens::get( $token_id );
            $wc_token->set_default('true');
            $pm_id = $wc_token->get_token();
            $wc_token->save();
        }
        else if( $new_card ){
            $pm_id = sanitize_text_field($_POST['mc_payment_method_id']);
        }


        // 无效的id
        if( empty($pm_id) ){
            $this->logger->error('payment method id is empty');
            throw new Exception(  __ ( 'Invalid payment method', 'moneycollect' ) );
        }

        $base_data = $this->base_data();
        $order_data = $this->order_data();

        if( $use_token ){
            $this->customer->update_payment_method($pm_id,$order_data['billingDetails']);
        }

        // 创建customer
        if( $save_card && !$this->customer->has_customer() ){
            $result = WC_MC_Payment_Api::create_customer($order_data['billingDetails']);
            if( $result['code'] === 'success' ){
                $this->customer->create_customer($result['data']['id']);
            }else{
                $this->logger->error($result['msg']);
            }
        }

        $data = [
            'orderNo' => $order_data['orderNo'],
            'amount' => $order_data['amountTotal'],
            'currency' => $order_data['currency'],
            'confirmationMethod' => 'automatic',
            'lineItems' => $order_data['lineItems'],
            'paymentMethod' => $pm_id,
            'customerId' =>$this->customer->get_id(),
            'ip' => $this->fun->get_ip(),
            'notifyUrl' => $base_data['notifyUrl'],
            'returnUrl' => $base_data['returnUrl'],
            'preAuth' => $base_data['preAuth'],
            'setupFutureUsage' => $save_card  ? 'on' : 'off',
            'statementDescriptor' => $base_data['statementDescriptor'],
            'website' => $base_data['website']
        ];
        if( isset($order_data['shipping']) ){
            $data['shipping'] = $order_data['shipping'];
        }

        $result = WC_MC_Payment_Api::create_payment($data);

        if( is_array($result) && isset($result['code']) ){

            $this->logger->payment($result);

            if( $result['code'] === 'success' ){
                $this->customer->clear_cache();
                $data = $result['data'];

                if( isset($data['nextAction']) && $data['nextAction']['type'] == 'redirect' ){
                    $redirect = $data['nextAction']['redirectToUrl'];
                    $this->order->add_order_note(__('Redirect to 3D page'));
                }else{
                    $redirect = add_query_arg( 'payment_id', $data['id'], $this->get_return_url( $this->order ) );
                }

                return array (
                    'result' => 'success',
                    'redirect' => $redirect
                );

            }else{
                throw new Exception($result['msg']);
            }
        }else{
            $this->logger->error($result);
            throw new Exception(  __ ( 'There was an accident', 'moneycollect' ) );
        }

    }

    protected function elements_form(){

        // 保存的卡
        if( is_user_logged_in() && is_checkout() && $this->get_option('save_card') == 'yes' ){
            $this->saved_payment_methods();
        }

        echo '<div id="wc-'.esc_attr( $this->id ).'-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;margin-top:10px">
            <div>'. __('Enter your card number','moneycollect') .'</div>
            <div id="mc-card-element" class="wc-asiabill-elements-field">
                <div id="card-element" class="mc-elemen" style="max-height: 44px;margin: 5px 0;"></div>
                <div id="mc-card-error" role="alert"></div>
            </div>
            </div>';

        // 保存的卡选项
        if( is_user_logged_in() && is_checkout() && $this->get_option('save_card') == 'yes' ){
            $this->save_payment_method_checkbox();
        }

    }

    function checkout_scripts(){

        if ( $this->enabled === 'no' ) {
            return;
        }

        wp_enqueue_script('mc_payment', WC_MC_Payment_Api::JSSDK, [], MONEYCOLLECT_VERSION, true);
        wp_enqueue_script('mc_checkout', MONEYCOLLECT_URL.'/assets/js/mc_checkout.js', ['jquery','mc_payment'], MONEYCOLLECT_VERSION, true);

        wp_localize_script(
            'mc_checkout',
            'mc_checkout_params',
            apply_filters( 'mc_checkout_params', $this->javascript_params() )
        );
        $this->tokenization_script();

    }

    protected function javascript_params(){
        $script_params = [
            'is_checkout' => ( is_checkout() && empty( $_GET['pay_for_order'] ) ) ? 'yes' : 'no', // wpcs: csrf ok.
            'apiKey' => $this->setting->get_pu_key(),
            'mode' => 'test',
            'layout' => [
                'pageMode' => $this->get_option('form_style'),// 页面风格模式  inner | block
                'style' => [
                    'frameMaxHeight' => $this->get_option('form_style') === 'inner' ? '44': '94', //  iframe最大高度
                    'input' => [
                        'FontSize' => '14', // 收集页面字体大小
                        'FontFamily' => '',  // 收集页面字体名称
                        'FontWeight' => '', // 收集页面字体粗细
                        'Color' => '', // 收集页面字体颜色
                        'ContainerBorder' => '1px solid #ddd;', // 收集页面字体边框
                        'ContainerBg' => '', // 收集页面字体粗细
                        'ContainerSh' => '' // 收集页面字体颜色
                    ]
                ],
            ],
        ];
        return $script_params;
    }


}