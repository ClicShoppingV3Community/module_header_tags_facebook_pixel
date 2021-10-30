<?php
  /**
   *
   * @copyright 2008 - https://www.clicshopping.org
   * @Brand : ClicShopping(Tm) at Inpi all right Reserved
   * @Licence GPL 2 & MIT
   * @licence MIT - Portion of osCommerce 2.4
   * @Info : https://www.clicshopping.org/forum/trademark/
   *
   */

  use ClicShopping\OM\Registry;
  use ClicShopping\OM\CLICSHOPPING;

  use ClicShopping\Sites\Shop\Pages\Checkout\Classes\CheckoutSuccess;

  class ht_facebook_pixel
  {
    public string $code;
    public $group;
    public $title;
    public $description;
    public ?int $sort_order = 0;
    public bool $enabled = false;

    public function __construct()
    {
      $this->code = get_class($this);
      $this->group = basename(__DIR__);

      $this->title = CLICSHOPPING::getDef('module_header_tags_facebook_pixel_title');
      $this->description = CLICSHOPPING::getDef('module_header_tags_facebook_pixel_description');

      if (\defined('MODULE_HEADER_TAGS_FACEBOOK_PIXEL_STATUS')) {
        $this->sort_order = MODULE_HEADER_TAGS_FACEBOOK_PIXEL_SORT_ORDER;
        $this->enabled = (MODULE_HEADER_TAGS_FACEBOOK_PIXEL_STATUS == 'True');
        if (empty(MODULE_HEADER_TAGS_FACEBOOK_PIXEL_ID) ) {
          $this->enabled = false;
        }
      }
    }

    public function execute()
    {
      $order_id = CheckoutSuccess::getCheckoutSuccessOrderId();

      if (!empty(MODULE_HEADER_TAGS_FACEBOOK_PIXEL_ID) && MODULE_HEADER_TAGS_FACEBOOK_PIXEL_STATUS == 'True') {
        $CLICSHOPPING_Template = Registry::get('Template');
        $CLICSHOPPING_Db = Registry::get('Db');
        $CLICSHOPPING_ProductsCommon = Registry::get('ProductsCommon');

        $header_tag = '<!--  Facebook Pixel Code start -->' . "\n";

        $header_tag .= '<script>' . "\n";
        $header_tag .= ' !public function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=public function(){ n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n; ';
        $header_tag .= 'n.push=n;n.loaded=!0;n.version=\'2.0\';n.queue=[];t=b.createElement(e);t.async=!0; ';
        $header_tag .= 't.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window, ';
        $header_tag .= 'document,\'script\',\'//connect.facebook.net/en_US/fbevents.js\'); ';
        $header_tag .= 'fbq(\'init\', \'' . MODULE_HEADER_TAGS_FACEBOOK_PIXEL_ID . '\'); ';

        $header_tag .= 'fbq(\'track\', "PageView"); ';
        $header_tag .= 'fbq(\'track\', \'ViewContent\'); ';
        $header_tag .= 'fbq(\'track\', \'Search\'); ';
        $header_tag .= 'fbq(\'track\', \'AddToCart\'); ';

        if ($CLICSHOPPING_ProductsCommon->getId()) {
          $header_tag .= 'fbq(\'track\', "ViewContent"); ';
          $header_tag .= 'content_type: \'product\', ';
          $header_tag .= 'content_ids: [' . (int)$CLICSHOPPING_ProductsCommon->getId() . '], ';
          $header_tag .= '}]) ' . "\n";
        }

        $header_tag .= '</script>' . "\n";

        if (isset($_GET['Checkout'], $_GET['Success'])) {

          $QorderTotal = $CLICSHOPPING_Db->prepare('select value
                                                     from :table_orders_total
                                                     where orders_id = :orders_id
                                                     and (class = :class || class = :class1)
                                                   ');
          $QorderTotal->bindInt(':orders_id', $order_id);
          $QorderTotal->bindValue(':class', 'ot_total');
          $QorderTotal->bindValue(':class', 'TO');
          $QorderTotal->execute();

          $Qorder = $CLICSHOPPING_Db->prepare('select currency
                                        from :table_order
                                        where orders_id = :orders_id
                                       ');
          $Qorder->execute();

          $header_tag .= '<script type="text/javascript">' . "\n";
          $header_tag .= 'fbq(\'track\', \'Purchase\', { ';
          $header_tag .= 'content_type: \'product\', ';
          $header_tag .= 'value: ' . number_format($QorderTotal->valueDecimal('value'), 2, '.', '');
          $header_tag .= ', currency:  ';
          $header_tag .= $Qorder->value('currency');
          $header_tag .= ' , order_id: ';
          $header_tag .= (int)$order_id;
          $header_tag .= ' , content_ids : ';

          $product_ids = '';

          $QorderProducts = $CLICSHOPPING_Db->prepare('select op.products_id,
                                                             pd.products_name,
                                                             op.final_price,
                                                             op.products_quantity
                                                      from :table_orders_products op,
                                                           :table_products_description pd,
                                                           :table_languages l
                                                      where op.orders_id = :orders_id
                                                      and op.products_id = pd.products_id
                                                      and l.code =:code
                                                     ');
          $QorderProducts->execute();

          $QorderProducts->bindInt(':orders_id', $order_id);
          $QorderProducts->bindValue(':code', DEFAULT_LANGUAGE);

          while ($QorderProducts->fetch()) {
            $product_ids .= (int)$QorderProducts->valueInt('products_id') . ','; // SKU/code - required
          }

          $header_tag .= '\'[' . rtrim($product_ids, ",") . '\]}); ';
          $header_tag .= '</script>' . "\n";
        }


        $header_tag .= '<!-- Facebook Pixel Code end -->' . "\n";

        $CLICSHOPPING_Template->addBlock($header_tag, $this->group);

        if ($CLICSHOPPING_ProductsCommon->getId()) {
          $footer_tag = '<!-- Facebook Pixel Code start -->' . "\n";
          $footer_tag .= '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . MODULE_HEADER_TAGS_FACEBOOK_PIXEL_ID . '&ev=PageView&noscript=1"  /></noscript>' . "\n";
          $footer_tag .= '<!-- Facebook Pixel Code end -->' . "\n";
        }

        $CLICSHOPPING_Template->addBlock($footer_tag, 'footer_scripts');

      }
    }

    public function isEnabled()
    {
      return $this->enabled;
    }

    public function check()
    {
      return \defined('MODULE_HEADER_TAGS_FACEBOOK_PIXEL_STATUS');
    }

    public function install()
    {
      $CLICSHOPPING_Db = Registry::get('Db');

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Do you want enable this module ?',
          'configuration_key' => 'MODULE_HEADER_TAGS_FACEBOOK_PIXEL_STATUS',
          'configuration_value' => 'True',
          'configuration_description' => 'Do you want enable this module ?',
          'configuration_group_id' => '6',
          'sort_order' => '1',
          'set_function' => 'clic_cfg_set_boolean_value(array(\'True\', \'False\'))',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Veuillez insérer le code Facebook Pixel ?',
          'configuration_key' => 'MODULE_HEADER_TAGS_FACEBOOK_PIXEL_ID',
          'configuration_value' => '',
          'configuration_description' => 'Le code est du type :  1664481970382387 que vous trouverez en créer votre Facebook Pixel. <br /> https://www.facebook.com/ads/manager/pixel/custom_audience_pixel/',
          'configuration_group_id' => '6',
          'sort_order' => '2',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );

      $CLICSHOPPING_Db->save('configuration', [
          'configuration_title' => 'Sort Order',
          'configuration_key' => 'MODULE_HEADER_TAGS_FACEBOOK_PIXEL_SORT_ORDER',
          'configuration_value' => '105',
          'configuration_description' => 'Sort order. Lowest is first',
          'configuration_group_id' => '6',
          'sort_order' => '85',
          'set_function' => '',
          'date_added' => 'now()'
        ]
      );

    }

    public function remove()
    {
      return Registry::get('Db')->exec('delete from :table_configuration where configuration_key in ("' . implode('", "', $this->keys()) . '")');
    }

    public function keys()
    {
      return ['MODULE_HEADER_TAGS_FACEBOOK_PIXEL_STATUS',
        'MODULE_HEADER_TAGS_FACEBOOK_PIXEL_ID',
        'MODULE_HEADER_TAGS_FACEBOOK_PIXEL_SORT_ORDER'
      ];
    }
  }
