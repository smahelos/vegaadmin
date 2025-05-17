<?php

return [
    'fields' => [
        'name' => 'Name',
        'description' => 'Description',
        'price' => 'Price',
        'sku' => 'SKU',
        'supplier_id' => 'Supplier',
        'category_id' => 'Category',
        'tax_id' => 'Tax',
        'is_default' => 'Default product',
        'is_active' => 'Active product',
        'image' => 'Image',
        'slug' => 'Slug',
        'currency' => 'Currency',
    ],

    'titles' => [
        'invoices' => 'Invoices',
        'index' => 'Products',
        'create' => 'Create product',
        'edit' => 'Edit product',
        'show' => 'Product #',
        'select_product' => 'Select product',
    ],

    'no_input_labels' => [
        'current_image' => 'Current image',
    ],

    'sections' => [
        'basic_info' => 'Basic information',
        'detail_info' => 'Details',
    ],

    'actions' => [
        'actions' => 'Actions',
        'create' => 'Create product',
        'edit' => 'Edit product',
        'delete' => 'Delete product',
        'view' => 'View product',
        'cancel' => 'Cancel',
        'save' => 'Save',
        'confirm_delete' => 'Confirm product deletion?',
        'back_to_list' => 'Back to list',
        'search' => 'Search product...',
    ],

    'hints' => [
        'delete' => 'Deleting a product will remove all related invoices and items.',
        'create' => 'Create a new product by filling in all required fields.',
        'edit' => 'Edit product information as needed.',
        'view' => 'View product details and its invoices.',
        'name' => '',
        'description' => '',
        'price' => '',
        'sku' => '',
        'supplier_id' => '',
        'category_id' => '',
        'tax_id' => '',
        'is_default' => '',
        'is_active' => '',
        'image' => '',
        'currency' => '',
        'slug' => 'Slug is a unique identifier for the product used in the URL.',
    ],

    'messages' => [
        'created' => 'Product was successfully created.',
        'updated' => 'Product was successfully updated.',
        'deleted' => 'Product was successfully deleted.',
        'error_create' => 'Error creating product.',
        'error_update' => 'Error updating product.',
        'error_delete' => 'Error deleting product.',
        'no_image' => 'No image available.',
        'no_image_selected' => 'No image selected yet.',
        'image_uploaded' => 'Image was successfully uploaded.',
        'image_deleted' => 'Image was successfully deleted.',
        'image_error' => 'Error uploading image.',
        'no_products_found' => 'No products found.',
    ],

    'validation' => [
        'not_found' => 'Product was not found.',
        'invalid_data' => 'Invalid data.',
        'api_error' => 'Error communicating with API.',
    ],

    'tags' => [
        'product' => 'Product',
        'invoice' => 'Invoice',
        'supplier' => 'Supplier',
        'category' => 'Category',
        'tax' => 'Tax',
        'details' => 'Details',
    ],

    'placeholders' => [
        'select_category' => 'Select category',
        'select_supplier' => 'Select supplier',
        'select_tax' => 'Select tax',
        'select_image' => 'Select image',
        'select_product' => 'Select product',
        'select_currency' => 'Select currency',
    ],
];
