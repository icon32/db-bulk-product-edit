<?php 


/**
 * Plugin Name:       Edit all products bulk
 * Description:       This plugin was created Edit bulk products
 * Version:           4.2
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


//// Update the product's weight meta field
//update_post_meta($product_id, '_weight', $new_weight);


function check_field_in_database() {
    if ( $_GET['sexy'] == 'engine') {

        // if ( is_user_logged_in()  && current_user_can( 'manage_options' )) {
            // User is logged in
            echo 'You can naw use the ultimate Tech .... :P  <br><br><br><br>';
        

            if(isset($_POST['product_per_page']) || isset($_GET['product_per_page'])){
                if (is_numeric($_POST['product_per_page']) || is_numeric($_GET['product_per_page'])) {
                    if($_POST['product_per_page']){
                        $chunk_size = $_POST['product_per_page'];
                    }
                    if($_GET['product_per_page']){
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
                <form method="post" action="/?crazy=mimikos">
                
                    <label for="name">Products Per Page:</label>
                    <input type="product_per_page" id="product_per_page" name="product_per_page" value="<?php if(isset($_POST['product_per_page'])){ echo $_POST['product_per_page'];}elseif(isset($_GET['product_per_page'])){ echo $_GET['product_per_page'];} ?>">
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

            if($display_pages){

                ?>  
                <style>
                    th , td{
                        border:1px solid black;
                        margin:3px;
                        padding:4px;
                    }
                </style>

                <?php
        
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
                if($chunk_size){
                    $chunks = array_chunk($ids, $chunk_size);
                    $output = $chunks[$page];
                }else{
                    $output = $ids;
                }
                // var_dump($output[0]);
                echo '<button id="selectAllBtn">Select All</button>';
                echo '<button id="edit" style="text-align:right;">Bulk Save Weight </button>';

                echo '<table>';
                echo '<tr>
                        <th>Select</th>
                        <th>No</th>
                        <th>ID</th>
                        <th>SKU</th>
                        <th>Origin</th>
                        <th>Name</th>
                        <th>Categorie</th>
                        <th>Normal Price</th>
                        <th>Price</th>
                        <th>Sale Price</th>
                        <th>Weight</th>
                        <th>Update Weight</th>
                        <th>Attribute Weight</th>
                        <th>Attribute Weight Value</th>
                        <th>Front Link</th>
                        <th>Back Link</th>

                    </tr>';
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
                            $fields .= '<td class="sku" >'.$_product->get_sku().'</td>';
                            $fields .= '<td class="origin">'.$product_origin.'</td>';
                            $fields .= '<td class="name">'.$_product->get_name().'</td>';
                            $fields .= '<td class="categories">'.implode(", ", $categoriess).'</td>';
                            $fields .= '<td class="normalprice">'.$_product->get_price().'</td>';
                            $fields .= '<td class="price">'.$_product->get_price_html().'</td>';
                            $fields .= '<td class="salelprice">'.$_product->get_sale_price().'</td>';
                            $fields .= '<td class="weight">'.$_product->get_weight().'</td>';
                            $fields .= '<td class="updateweight"><input type="number" style="width:50px;" name="product-'.$productID.'" value="'.$_product->get_weight().'">kg <br><button onclick="changeWeight('.$productID.')" id="product-'.$productID.'">Save</button></td>';
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
                            $fields .= ($weight_att) ? $weight_att : '<td></td> <td></td>';
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
                ?>
                <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

                    <script>

                            function changeWeight(productid){
                                newweight = $('input[name="product-'+productid+'"]').val();
                                console.log('Weight Changed for product: '+ productid + ' to ' + newweight);
                            }


                        jQuery(document).ready(function(){
                            var selectall = false;

                            $('#selectAllBtn').on('click', function(){
                                if(selectall == false){
                                    $('.product-checkbox').prop('checked', true);
                                    selectall = true
                                }else{
                                    $('.product-checkbox').prop('checked', false);
                                    selectall = false
                                }
                            });

                            

                            $('#edit').on('click', function(){
                                // Select all checked checkboxes with class 'mycheck' and get their data-layer attribute
                                $('.product-checkbox:checked').each(function() {
                                    var dataLayer = $(this).data('layer');
                                    changeWeight(dataLayer)
                                    // console.log(dataLayer);
                                });
                            });
                        });
                    </script>
                <?php

                echo '<br><br>Pages: <br>';
                $pages = count($chunks);
                for($i=0;$i <= $pages;$i++){
                    
                    echo '<a href="/?crazy=mimikos&product_per_page='.$chunk_size.'&page='.$i.'">'.$i.'</a> / ';
                }
                    
            }

            die;
        // }
    }
}
add_action( 'init', 'check_field_in_database' );