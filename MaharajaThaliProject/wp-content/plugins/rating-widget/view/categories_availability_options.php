<?php
    // Get all post categories.
    $all_categories = get_categories();
    
    if (is_array($all_categories) && count($all_categories) > 0)
    {
        $all = in_array("-1", rw_settings()->categories);
?>
<div id="rw_categories_availability_settings" class="has-sidebar has-right-sidebar">
    <div class="has-sidebar-content">
        <div class="postbox rw-body">
            <h3><?php _e('Categories Visibility Settings', WP_RW__ID) ?></h3>
            <div class="inside rw-ui-content-container rw-no-radius">
                <div class="rw-ui-img-radio rw-ui-hor rw-select-all<?php if ($all) echo ' rw-selected';?>">
                    <input type="checkbox" name="rw_categories[]" value="-1" <?php if ($all) echo ' checked="checked"';?>> <span><?php _e('All Categories', WP_RW__ID) ?></span>
                </div>
<?php
                foreach ($all_categories as $category)
                {
                    $selected = ($all || in_array($category->cat_ID, rw_settings()->categories));
?>
                <div class="rw-ui-img-radio rw-ui-hor<?php if ($selected) echo ' rw-selected';?>">
                    <input type="checkbox" name="rw_categories[]" value="<?php echo $category->cat_ID;?>" <?php if ($selected) echo ' checked="checked"';?>> <span><?php echo $category->cat_name;?></span>
                </div>
<?php                    
                }
?>
            </div>
        </div>
    </div>
</div>
<?php
    }
?>