<?php 


/**
 * Plugin Name:       Edit all products bulk
 * Description:       This plugin was created Edit bulk products
 * Version:           1.4
 * Text Domain:       dbtextdomain
 * Domain Path:       /language
 * Requires at least: 5.0
 * Requires PHP:      5.6
 * Author:            Dionisis Bolanis
 * Author URI:        https://bolanis.eu/
 * License:           GPL v2 or later
 * Woo: //WooCommerce core looks for a Woo line in the plugin header comment, to ensure it can check for updates to your plugin, on WooCommerce.com.
 * WC requires at least: 2.2
 * WC tested up to: 3.6
 */

function db_bulk_edit_endpoint() {
    register_rest_route( 'db-bulk-edit/v1', '/get', array(
        array(
            'methods'  => WP_REST_Server::READABLE, // GET request
            'callback' => 'db_edit_fields_call', // Call Back Function to serve the content
            'permission_callback' => 'db_bulk_edit_restrict_endpoint'  // Permission Function to restrict Access
        ),
        array(
            'methods'  => WP_REST_Server::CREATABLE, // POST request
            'callback' => 'db_edit_fields_call', // Call Back Function to serve the content
            'permission_callback' => 'db_bulk_edit_restrict_endpoint',  // Permission Function to restrict Access
        ),
    ) );
}
add_action( 'rest_api_init', 'db_bulk_edit_endpoint' );

function db_bulk_edit_restrict_endpoint() {
    // Check the Referer or Origin header to allow requests only from your domain
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';    
    //remove http:// or https://  from incoming call url
    $referer = str_replace("http://", "", $referer);
    $origin = str_replace("http://", "", $origin);
    $referer = str_replace("https://", "", $referer);
    $origin = str_replace("https://", "", $origin);
    //Break the url to array in order to keep the first cell which must be the domain 
    $arrayone = explode("/", $referer);
    $arraytwo = explode("/", $origin);
    //remove http:// from site url
    $site_url = get_home_url();
    $site_domain = parse_url($site_url, PHP_URL_HOST);
    $allowed_domain = $site_domain;
    //Check if the call comes from this website
    if ($arrayone[0] === $allowed_domain || $arraytwo[0] === $allowed_domain) {
        return true; // Access allowed for requests from your domain
    }
    return new WP_Error('rest_forbidden', esc_html__('Unauthorized access.', 'your-text-domain'), array('status' => 401));
}

// Callback function for the custom endpoint
function db_edit_fields_call( $request ) {
    $method = $request->get_method();
    $data = $request->get_json_params();
    $product = wc_get_product( $data['product_id'] );
    if($data['field_name'] == 'name'){
        $product->set_name( $data['field_value']);
    }
    if($data['field_name'] == 'salelprice'){
        $product->set_sale_price( $data['field_value']);
    }
    if($data['field_name'] == 'normalprice'){
        $product->set_regular_price( $data['field_value']);
    }
    $product->save();
    if($data['field_name'] == 'weight'){
        update_post_meta($data['product_id'], '_weight', $data['field_value']);
    }    
    if($data['field_name'] == 'sku' ){
      $chabge_ok =  update_post_meta($data['product_id'], '_sku', $data['field_value']);
    }    
    if($data['field_name'] == 'origin'){
        update_post_meta($data['product_id'], 'product_origin', $data['field_value']);
    }
    $result = array('status' => true , 'data' => $data);
    // $result = array('status' => true , 'data' => $data['sku']);
    return rest_ensure_response( $result );   //Return to the fron end
}

function db_call_function_javascript(){
    ?>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script>
            let saveFieldRunning = false;
            function saveField(productID,fieldname){
                var fieldValue = $('.' + productID +' .'+fieldname + ' input[name="'+fieldname+'"]').val();
                // console.log('Weight Changed for product: '+ productid + ' to ' + newweight);
                var data = {product_id:productID , field_name:fieldname , field_value:fieldValue}
                $.ajax({
                    url: '/wp-json/db-bulk-edit/v1/get',
                    method: 'POST',
                    data: JSON.stringify(data),
                    // data: data,
                    contentType: 'application/json',
                    dataType: 'json',
                    success: function(response) {
                        if(response.status == true){
                            $('.' + productID +' .'+fieldname).html(fieldValue);
                            $('.' + productID +' .'+fieldname).css('background', 'lightgreen');
                            console.log(response); // Handle any errors
                        }else{
                            $('.' + productID +' .'+fieldname).html(fieldValue);
                            $('.' + productID +' .'+fieldname).css('background', 'lightred');
                            console.log(response); // Handle any errors
                        }
                    },
                    error: function(xhr, status, error) {
                        // $('.' + productID +' .'+fieldname).html(fieldValue);
                        $('.' + productID +' .'+fieldname).css('background', 'lightred');
                        console.log( error); // Handle any errors
                    }
                });
            }
            jQuery(document).ready(function(){
                var selectall = false;
                $('.edit-field-button').on('click', function(){
                    var fieldname = $(this).closest('td').attr('class');
                    var productId = $(this).closest('tr').attr('class');
                    var originalString = $(this).closest('td').html();
                    var modifiedString = originalString.replace('<button class="edit-field-button">Edit</button>', '');
                    $(this).closest('td').html('<input type="text" name="'+fieldname+'" value="'+modifiedString+'"><button onclick="saveField('+productId+', `'+fieldname+'`)">Save</button>')
                    // $(this).
                    $('.'+productId +' .product-checkbox').prop('checked', true);
                });

                $('#selectAllBtn').on('click', function(){
                    if(selectall == false){
                        $('.product-checkbox').prop('checked', true);
                        selectall = true
                    }else{
                        $('.product-checkbox').prop('checked', false);
                        selectall = false
                    }
                });

                $('#editall').on('click', function(){
                    // Select all checked checkboxes with class 'mycheck' and get their data-layer attribute
                    $('.product-checkbox:checked').each(function() {
                        var dataLayer = $(this).data('layer');
                        var tdsInRow = $('.'+dataLayer).find('td');
                        tdsInRow.each(function(index) {
                            var tdContent = $(this).html(); // Get the HTML content of the td
                            if (tdContent.includes('<button class="edit-field-button">Edit</button>')) {
                                var tdClass = $(this).attr('class');
                                var modifiedString = tdContent.replace('<button class="edit-field-button">Edit</button>', '');
                                $(this).html('<input type="text" name="'+tdClass+'" value="'+modifiedString+'"><button onclick="saveField('+dataLayer+', `'+tdClass+'`)">Save</button>')
                            }
                        });
                    });
                });

                $('#saveall').on('click', function(){
                    // Select all checked checkboxes with class 'mycheck' and get their data-layer attribute
                    $('.product-checkbox:checked').each(function() {
                        var dataLayer = $(this).data('layer');
                        var tdsInRow = $('.'+dataLayer).find('td');
                        tdsInRow.each(function(index) {
                            var tdClass = $(this).attr('class');
                            var tdContent = $(this).html(); // Get the HTML content of the td
                            if (tdContent.includes('<button onclick="')) {
                                saveField(dataLayer,tdClass);
                            }
                        });
                    });
                });
            });
        </script>
    <?php
}

function check_field_in_database() {
    if ( isset($_GET['sexy']) == 'engine') {

        if ( is_user_logged_in()  && current_user_can( 'manage_options' )) {
            ?>  
                <style>
                    th , td{
                        border:1px solid black;
                        margin:3px;
                        padding:4px;
                        text-align:center;
                    }

                    .sku{
                        width:100px;
                    }
                    .origin{
                        width:100px;
                    }
                    .categories{
                        width:200px;
                    }

                    .normalprice{
                        width:100px;
                    }

                    .salelprice{
                        width:100px;
                    }

                    .weight{
                        width:100px;
                    }

                    .name{
                        width:200px;
                    }
                    .checkboxes , .count{
                        width:30px;
                    }
                    table{
                        width:2000px;
                    }
                    button , input[type="submit"]{
                        padding:8px;
                        background:lightblue;
                        border-radius:8px;
                        border:0px;
                        margin-left:10px;
                        cursor: pointer;
                    }
                    input[type="text"]{
                        padding:5px;
                        border-radius:8px;
                        border:1px solid blue;

                    }
                </style>

            <?php
            // User is logged in
            echo '<h1>Bulk Editor by DB</h1><br>';

            echo 'You can now use the ultimate Tech .... :P  <br><br><br><br>';
            if(isset($_POST['product_per_page']) ){
                if (is_numeric($_POST['product_per_page']) ) {
                    if($_POST['product_per_page']){
                        $chunk_size = $_POST['product_per_page'];
                    }
                    if(isset($_GET['product_per_page'])){
                        $chunk_size = $_GET['product_per_page'];
                    }
                    $display_pages = true;
                    if(isset($_GET['page'])){
                        $page = $_GET['page'];
                    }else{
                        $page = 0;
                    }     
                }else{
                    echo "You Need to insert a numer.";
                }
            }
            if(isset($_GET['product_per_page']) ){
                if ( is_numeric($_GET['product_per_page'])) {


                    if(isset($_GET['product_per_page'])){
                        $chunk_size = $_GET['product_per_page'];
                    }
                    $display_pages = true;
                    if($_GET['page']){
                        $page = $_GET['page'];
                    }else{
                        $page = 0;
                    }
                    
                }else{
                    echo "You Need to insert a numer.";
                }
            }
            if(isset($_POST['all_products'])){
                $display_pages = true;
            }

            ?>
                <form method="post" action="/?sexy=engine">
                
                    <label for="name">Products Per Page:</label>
                    <input type="text" id="product_per_page" name="product_per_page" value="<?php if(isset($_POST['product_per_page'])){ echo $_POST['product_per_page'];}elseif(isset($_GET['product_per_page'])){ echo $_GET['product_per_page'];} ?>">
                    <input type="submit" value="Submit">
                </form>
                - OR - <br>
                <form method="post" action="">
                    <input type="hidden" id="all_products" name="all_products" >
                    Select By Categorie
                    <select class="product-categories" name="productcategories">
                    <?php 
                    
                        $terms = get_terms('product_cat');
                        if (!empty($terms) && !is_wp_error($terms)) {
                            echo '<option value="none" >Show All</option>'; // Outputting category names

                            foreach ($terms as $term) {
                                if($_POST['productcategories'] == $term->slug){
                                    $checked = 'selected';
                                }else{
                                    $checked = '';
                                }
                                echo '<option value="'.$term->slug . '" '.$checked.'>'.$term->name . '</option>'; // Outputting category names
                            }
                        }
                    ?>
                    </select>
                    <input type="submit" value="Display All Products">
                </form>
            <?php

            if(isset($display_pages)){

                
        
                // global $wpdb;
                // $results = $wpdb->get_results("SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_status = 'publish'");
                $count = 0;
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids', // Retrieve only IDs
                    'tax_query' => array(),
                );

                if(isset($_POST['productcategories']) && $_POST['productcategories'] != 'none' ){
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => 'product_cat', // Taxonomy name for categories
                            'field' => 'slug', // Use 'slug' or 'term_id' or 'name' here
                            'terms' => array($_POST['productcategories']), // Specify your category slugs/terms here
                            'operator' => 'IN' // 'IN' or 'AND' to filter by multiple categories
                        )
                    );
                }

                
                
                $query = new WP_Query($args);
                // var_dump($query->posts);
                
                $ids = array();
                foreach($query->posts as $result){
                    $ids[] = $result;
                
                }
                if(isset($chunk_size)){
                    $chunks = array_chunk($ids, $chunk_size);
                    $output = $chunks[$page];
                }else{
                    $output = $ids;
                }
                // var_dump($output[0]);
                echo '<button id="selectAllBtn">Select All</button>';
                echo '<button id="editall" style="text-align:right;">Bulk Edit Selected </button>';
                echo '<button id="saveall" style="text-align:right;">Bulk Save </button>';
                echo '<br><br>';

                echo '<table>';
                $header_display = '<tr>';
                    $header_display .= '<th>Select</th>';
                    $header_display .= '<th>No</th>';
                    $header_display .= '<th>ID</th>';
                    $header_display .= '<th>SKU</th>';
                    $header_display .= '<th>Type</th>';
                    // $header_display .= '<th>Origin</th>';
                    $header_display .= '<th>Name</th>';
                    $header_display .= '<th>Categorie</th>';
                    $header_display .= '<th>Normal Price</th>';
                    $header_display .= '<th>Price</th>';
                    $header_display .= '<th>Sale Price</th>';
                    $header_display .= '<th>Weight (kg)</th>';
                    // $header_display .= '<th>Attribute Weight</th>';
                    // $header_display .= '<th>Attribute Weight Value</th>';
                    $header_display .= '<th>Front Link</th>';
                    $header_display .= '<th>Back Link</th>';
                $header_display .= '</tr>';
                echo $header_display;
                foreach($output as $result){
                    $productID = $result;
                    $_product = get_product( $productID );
                    $categories = $_product->get_category_ids();
                    $product_origin = get_post_meta($productID, 'product_origin', true); 


                    // var_dump($categories);
                    $categoriess = array();
                    foreach($categories as $cat_id ){
                        $term = get_term_by( 'id', $cat_id, 'product_cat' );
                        $categoriess[] = $term->name;
                    }
                    
                    if(!empty($categoriess)){
                        $fields = '<tr class="'.$productID.'">';
                        
                            $fields .= '<td class="checkboxes"><input class="product-checkbox" type="checkbox" data-layer="'.$productID.'"></td>';
                            $fields .= '<td class="count">'.$count.'</td>';
                            $fields .= '<td class="id" >'.$productID.'</td>';
                            $fields .= '<td class="sku" >'.$_product->get_sku().'<button class="edit-field-button" >Edit</button></td>';
                            $fields .= '<td class="type" >'.$_product->get_type().'</td>';
                            // $fields .= '<td class="origin">'.$product_origin.'<button class="edit-field-button" >Edit</button></td>';
                            $fields .= '<td class="name">'.$_product->get_name().'<button class="edit-field-button" >Edit</button></td>';
                            $fields .= '<td class="categories">'.implode(", ", $categoriess).'</td>';
                            $fields .= '<td class="normalprice">'.$_product->get_price().'<button class="edit-field-button" >Edit</button></td>';
                            $fields .= '<td class="price">'.$_product->get_price_html().'</td>';
                            $fields .= '<td class="salelprice">'.$_product->get_sale_price().'<button class="edit-field-button" >Edit</button></td>';
                            $fields .= '<td class="weight">'.$_product->get_weight().'<button class="edit-field-button" >Edit</button></td>';
                            $attributes = $_product->get_attributes();
                            $weight_att = '';
                            foreach($attributes as $p_attributes => $values){
                                if(str_contains($p_attributes, 'varos')){
                                    $label_name = get_taxonomy( $p_attributes )->labels->singular_name;
                                    $weight_att .= '<td class="attribute_'.$p_attributes.'_label">'.$label_name.'</td>';
                                    $vv = $_product->get_attribute( $p_attributes );
                                    $weight_att .= '<td class="attribute_'.$p_attributes.'_value">'.$vv.'</td>';
                                }
                            }
                            // $fields .= ($weight_att) ? $weight_att : '<td></td> <td></td>';
                            $fields .= '<td class="frontLink"><a href="'.get_permalink( $_product->get_id() ).'">Here</a></td>';
                            $fields .= '<td class="backlink"><a href="/wp-admin/post.php?post='.$_product->get_id().'&action=edit">Here</a></td>';

                        $fields .= '</tr>';
                        echo $fields;
                        $count++;
                    }
                    // if($count == 100){
                    //     break;
                    // }
                    
                
                }
                echo '</table>';
                
                db_call_function_javascript();

                echo '<br><br>Pages: <br>';
                if(isset($chunks)){
                    $pages = count($chunks);
                    for($i=0;$i <= $pages;$i++){
                        echo '<a href="/?sexy=engine&product_per_page='.$chunk_size.'&page='.$i.'">'.$i.'</a> / ';
                    }
                }
                
                    
            }

            die;
        }
    }
}
add_action( 'init', 'check_field_in_database' );