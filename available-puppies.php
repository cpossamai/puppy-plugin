<?php
/*
Plugin Name: Available Puppies
Description: A simple plugin to upload and display available puppies images.
Version: 1.0
Author: Colin Possamai
*/

// Enqueue custom styles for the admin page
function puppies_admin_styles()
{
    echo '
    <style>
        /* General Form Container Styles */
        .puppies-form-container {
            background-color: #baac91;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Form Column Styles */
        .puppies-form-column {
            background-color: #f5f5f5; /* Light background for better contrast */
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }

        /* Heading Styles */
        .puppies-form-container h3 {
            font-family: Arial, sans-serif;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        /* Label Styles */
        .puppies-form-container label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        /* Input and Textarea Styles */
        .puppies-form-container input[type="text"],
        .puppies-form-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        /* Thumbnail Styles for Uploaded Images */
        .puppies-form-container img.thumbnail {
            max-width: 100px; /* Thumbnail size */
            max-height: 100px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-right: 10px;
            vertical-align: middle;
        }

        /* Checkbox Styles */
        .puppies-form-container input[type="checkbox"] {
            margin-right: 5px;
        }

        /* Submit Button Styles */
        .puppies-form-container input[type="submit"] {
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .puppies-form-container input[type="submit"]:hover {
            background-color: #555;
        }
        /* Two Column Layout */
.two-columns {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 20px; /* Adjust as needed */
}
.puppy-image-container {
    position: relative;
    display: inline-block;
}

div.work-item.style-1 {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

div.work-item.style-1 > .sold-overlay {
    position: absolute;
    z-index: 1000;
}






    </style>
    ';
}
add_action('admin_head', 'puppies_admin_styles');

function puppies_admin_page()
{
    add_menu_page('Available Puppies', 'Available Puppies', 'manage_options', 'available-puppies', 'puppies_admin_page_callback');
}

function puppies_admin_page_callback()
{
    // Check if the current user has the required capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // Array to store any upload errors
    $errors = [];

    // Check if form is submitted
    if (isset($_POST['submit'])) {
        // Check nonce for security
        if (!isset($_POST['puppies_nonce']) || !wp_verify_nonce($_POST['puppies_nonce'], 'puppies_upload')) {
            echo '<div class="error"><p>Security check failed!</p></div>';
            return;
        }
        // Save meta description
        if (isset($_POST['meta_description'])) {
            update_option('puppies_meta_description', sanitize_textarea_field($_POST['meta_description']));
        }

        // Handle the image upload logic for puppies
        for ($i = 1; $i <= 10; $i++) {

            // Save puppy names and descriptions

            if (isset($_POST['puppy_name_' . $i])) {
                update_option('puppy_name_' . $i, sanitize_text_field($_POST['puppy_name_' . $i]));
            }
            if (isset($_POST['puppy_desc_' . $i])) {
                update_option('puppy_desc_' . $i, sanitize_textarea_field($_POST['puppy_desc_' . $i]));
            }



            if (isset($_FILES['puppy_image_' . $i]) && $_FILES['puppy_image_' . $i]['size'] > 0) {
                // Validate file type (only allow jpg, jpeg, png, gif)
                $file_type = wp_check_filetype(basename($_FILES['puppy_image_' . $i]['name']));
                $allowed_file_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');

                if (!in_array($file_type['type'], $allowed_file_types)) {
                    $errors[] = "File type for image {$i} is not allowed.";
                    continue;
                }

                // Use WordPress upload function
                $upload_overrides = array('test_form' => false);
                $uploaded_image = wp_handle_upload($_FILES['puppy_image_' . $i], $upload_overrides);

                if (isset($uploaded_image['file'])) {
                    // Insert the image into the media library
                    $attachment = array(
                        'post_mime_type' => sanitize_mime_type($file_type['type']),
                        'post_title' => sanitize_file_name($uploaded_image['file']),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    );
                    $attachment_id = wp_insert_attachment($attachment, $uploaded_image['file']);
                    update_option('puppy_image_' . $i, $attachment_id);
                } else {
                    // Error in uploading
                    $errors[] = $uploaded_image['error'];
                }
            } elseif (isset($_POST['remove_puppy_image_' . $i])) {
                // Handle image removal
                delete_option('puppy_image_' . $i);
            }




            // After saving the puppy details, check if a page for the puppy already exists
            $puppy_name = get_option('puppy_name_' . $i);
            $puppy_desc = get_option('puppy_desc_' . $i);
            $puppy_image_id = get_option('puppy_image_' . $i);

            if ($puppy_name && $puppy_desc && $puppy_image_id) {
                $puppy_image_url = wp_get_attachment_url($puppy_image_id);
                $page_content = '<img src="' . esc_url($puppy_image_url) . '" alt="' . esc_attr($puppy_desc) . '">';
                $page_content .= '<p>' . esc_html(stripslashes($puppy_desc)) . '</p>';
                for ($j = 1; $j <= 4; $j++) {
                    $additional_image_id = get_option('puppy_additional_image_' . $i . '_' . $j);
                    if ($additional_image_id) {
                        $additional_image_url = wp_get_attachment_url($additional_image_id);
                        $page_content .= '<img src="' . esc_url($additional_image_url) . '" alt="Additional Image ' . $j . '" class="thumbnail">';
                    }
                }

                // Check if a page with the puppy's name already exists
                $existing_page = get_page_by_title($puppy_name, OBJECT, 'page');

                if ($existing_page) {
                    // Update the existing page
                    $page_data = array(
                        'ID' => $existing_page->ID,
                        'post_content' => $page_content,
                    );
                    wp_update_post($page_data);
                } else {
                    // Create a new page for the puppy
                    $page_data = array(
                        'post_title' => wp_strip_all_tags($puppy_name),
                        'post_content' => $page_content,
                        'post_status' => 'publish',
                        'post_type' => 'page',
                    );
                    wp_insert_post($page_data);
                }
            }



            for ($j = 1; $j <= 4; $j++) {
                if (isset($_FILES['puppy_additional_image_' . $i . '_' . $j]) && $_FILES['puppy_additional_image_' . $i . '_' . $j]['size'] > 0) {
                    // ... (similar image upload logic as the primary image)
                    // Validate file type (only allow jpg, jpeg, png, gif)
                    if (isset($_FILES['puppy_additional_image_' . $i . '_' . $j]['name'])) {
                        $file_type = wp_check_filetype(basename($_FILES['puppy_additional_image_' . $i . '_' . $j]['name']));
                    } else {
                        continue; // Skip the current iteration of the loop if the key doesn't exist
                    }

                    $allowed_file_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');

                    if (!in_array($file_type['type'], $allowed_file_types)) {
                        $errors[] = "File type for image {$i} is not allowed.";
                        continue;
                    }

                    // Use WordPress upload function
                    $upload_overrides = array('test_form' => false);
                    $uploaded_image = wp_handle_upload($_FILES['puppy_additional_image_' . $i . '_' . $j], $upload_overrides);


                    if (isset($uploaded_image['file'])) {
                        // Insert the image into the media library
                        $attachment = array(
                            'post_mime_type' => sanitize_mime_type($file_type['type']),
                            'post_title' => sanitize_file_name($uploaded_image['file']),
                            'post_content' => '',
                            'post_status' => 'inherit'
                        );
                        $attachment_id = wp_insert_attachment($attachment, $uploaded_image['file']);
                        update_option('puppy_additional_image_' . $i . '_' . $j, $attachment_id);

                    } else {
                        // Error in uploading
                        $errors[] = $uploaded_image['error'];
                    }

                    // Save the uploaded image ID to the database
                    update_option('puppy_additional_image_' . $i . '_' . $j, $attachment_id);
                } elseif (isset($_POST['remove_puppy_additional_image_' . $i . '_' . $j])) {
                    // Handle image removal
                    delete_option('puppy_additional_image_' . $i . '_' . $j);
                }
            }
            if (isset($_POST['puppy_sold_' . $i])) {
                update_option('puppy_sold_' . $i, '1'); // Mark as sold
            } else {
                delete_option('puppy_sold_' . $i); // Remove the sold status
            }
        }

        // Display any errors
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<div class="error"><p>' . esc_html($error) . '</p></div>';
            }
        } else {
            echo '<div class="updated"><p>Details saved successfully!</p></div>';
        }
    }

    // Display the form
    echo '<form method="post" enctype="multipart/form-data" class="puppies-form-container">';

    // Add the nonce field to the form
    echo '<input type="hidden" name="puppies_nonce" value="' . wp_create_nonce('puppies_upload') . '">';

    // Add the meta description input field
    echo '<label for="meta_description">Meta Description:</label>';
    echo '<textarea name="meta_description" id="meta_description" rows="4">' . get_option('puppies_meta_description') . '</textarea>';

    echo '<div class="puppies-form-column">'; // Start of first column

    for ($i = 1; $i <= 10; $i++) {
        echo '<label>Puppy ' . $i . ' Name:</label>';
        echo '<input type="text" name="puppy_name_' . $i . '" value="' . get_option('puppy_name_' . $i) . '"><br>';
        echo '<label>Puppy ' . $i . ' Description:</label>';
        echo '<textarea name="puppy_desc_' . $i . '">' . stripslashes(get_option('puppy_desc_' . $i)) . '</textarea><br>';
        // Checkbox to mark the puppy as sold
        $is_sold = get_option('puppy_sold_' . $i) ? 'checked' : '';
        echo '<input type="checkbox" name="puppy_sold_' . $i . '" ' . $is_sold . '> Mark as Sold<br>';


        $puppy_image_id = get_option('puppy_image_' . $i);
        if ($puppy_image_id) {
            $puppy_image_url = wp_get_attachment_url($puppy_image_id);
            echo '<img src="' . esc_url($puppy_image_url) . '" alt="' . esc_attr(get_option('puppy_name_' . $i)) . '" class="thumbnail"><br>';
            echo '<input type="file" name="puppy_image_' . $i . '"><br>';
            echo '<input type="checkbox" name="remove_puppy_image_' . $i . '"> Remove Image<br>';
        } else {
            echo '<input type="file" name="puppy_image_' . $i . '"><br>';
        }

        /* for ($j = 1; $j <= 4; $j++) {
             $previous_image_id = ($j > 1) ? get_option('puppy_additional_image_' . $i . '_' . ($j - 1)) : $puppy_image_id;
             if ($previous_image_id) {
                 $additional_image_id = get_option('puppy_additional_image_' . $i . '_' . $j);
                 if ($additional_image_id) {
                     $additional_image_url = wp_get_attachment_url($additional_image_id);
                     echo '<img src="' . esc_url($additional_image_url) . '" alt="Additional Image ' . $j . '" class="thumbnail"><br>';
                 }
                 echo '<input type="file" name="puppy_additional_image_' . $i . '_' . $j . '"><br>';
                 echo '<input type="checkbox" name="remove_puppy_additional_image_' . $i . '_' . $j . '"> Remove Additional Image ' . $j . '<br>';
             }
         }*/
        for ($j = 1; $j <= 4; $j++) {
            $additional_image_id = get_option('puppy_additional_image_' . $i . '_' . $j);
            if ($additional_image_id) {
                $additional_image_url = wp_get_attachment_url($additional_image_id);
                echo '<img src="' . esc_url($additional_image_url) . '" alt="Additional Image ' . $j . '" class="thumbnail"><br>';
                echo '<input type="file" name="puppy_additional_image_' . $i . '_' . $j . '"><br>';
                echo '<input type="checkbox" name="remove_puppy_additional_image_' . $i . '_' . $j . '"> Remove Additional Image ' . $j . '<br>';
            } else {
                $previous_image_id = ($j > 1) ? get_option('puppy_additional_image_' . $i . '_' . ($j - 1)) : $puppy_image_id;
                if ($previous_image_id) {

                    echo '<input type="file" name="puppy_additional_image_' . $i . '_' . $j . '"><br>';
                }

            }
        }
    }
    echo '</div>'; // End of first column

    echo '<div class="puppies-form-column">'; // Start of second column


    echo '<div style="width: 100%; padding-top: 20px;"><input type="submit" name="submit" value="Upload & Save Details"></div>';
    echo '</form>';
}

function display_puppies_shortcode()
{
    $output = '<div class="portfolio-wrap">';

    $output .= '<span class="portfolio-loading none"></span>';

    $output .= '<div class="row portfolio-items no-masonry constrain-max-cols" data-rcp="false" data-masonry-type="default" data-ps="1" data-starting-filter="default" data-gutter="default" data-categories-to-show="our-dogs" data-bypass-cropping="" data-lightbox-only="0" data-col-num="cols-4">';

    // Variable to check if there are any puppies
    $hasPuppies = false;

    for ($i = 1; $i <= 10; $i++) {
        $puppy_name = get_option('puppy_name_' . $i);
        $puppy_desc = get_option('puppy_desc_' . $i);
        $puppy_image_id = get_option('puppy_image_' . $i);
        $is_sold = get_option('puppy_sold_' . $i); // Retrieve the "sold" status

        if ($puppy_image_id) { // Only display if there's an image
            $puppy_image_url = wp_get_attachment_url($puppy_image_id);
            $hasPuppies = true; // Set to true as there's at least one puppy

            $output .= '<div class="col span_3 element our-dogs" data-project-cat="our-dogs" data-default-color="true">';
            $output .= '<div class="inner-wrap animated" data-animation="none">';
            $output .= '<div class="work-item style-1" data-custom-content="off">';


            // Check if the puppy is sold
            $is_sold = get_option('puppy_sold_' . $i);
            if ($is_sold) {
                $output .= '<div class="sold-overlay"><img src="http://elfore-cavaliers.local/wp-content/themes/salient/img/puppy_sold.png" alt="Sold"></div>';
            }



            $puppy_page_link = get_permalink(get_page_by_title($puppy_name)->ID);
            $output .= '<a href="' . esc_url($puppy_page_link) . '"><img class="size- skip-lazy" src="' . esc_url($puppy_image_url) . '" alt="' . esc_attr($puppy_desc) . '" title="' . esc_attr($puppy_name) . '"></a>';
            $output .= '<div class="work-info-bg"></div>';
            $output .= '<div class="work-info">';
            $output .= '<div class="vert-center">';
            $output .= '<a href="' . esc_url($puppy_image_url) . '" class="pretty_photo default-link">View Larger</a>';
            $output .= '</div>'; // End of vert-center
            $output .= '</div>'; // End of work-info

            $output .= '</div>'; // End of work-item
            $output .= '<div class="work-meta">';
            $output .= '<h4 class="title">' . esc_html($puppy_name) . '</h4>';
            $output .= '<p class="puppy-description">' . nl2br(esc_html(stripslashes($puppy_desc))) . '</p>';

            $output .= '</div>'; // End of work-meta

            $output .= '</div>'; // End of inner-wrap
            $output .= '</div>'; // End of col
        }
    }
    $output .= '</div>'; // End of portfolio-items
    $output .= '</div>'; // End of portfolio-wrap

    // If there are no puppies, display the message
    if (!$hasPuppies) {
        $output = '<h2>There are no puppies at this time, please check <a href="https://rightpaw.com.au/l/elfore-cavaliers/5182e64b-350a-4f2c-b21d-be1915d3fd2e">RightPaw page</a> for the next expected litter.</h2>';
    }

    return $output;
}

function add_custom_meta_description()
{
    $meta_description = get_option('puppies_meta_description');
    if ($meta_description) {
        echo '<meta name="description" content="' . esc_attr($meta_description) . '">';
    }
}


add_action('wp_head', 'add_custom_meta_description');

add_action('admin_menu', 'puppies_admin_page');
add_shortcode('available_puppies', 'display_puppies_shortcode');

?>